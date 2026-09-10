<?php
// app/Http/Controllers/PosController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Exception;

class PosController extends Controller
{
    // 1. عرض واجهة نقطة البيع (POS)
    public function index()
    {
        // جلب السلع مع معلومات الصنف والوحدة (Eager Loading لضمان السرعة)
        $products = Product::with(['category', 'unit'])->get();
        $customers = Customer::all();
        
        return view('pos.index', compact('products', 'customers'));
    }

    // 2. معالجة عملية البيع وحفظ الفاتورة
    public function store(Request $request)
    {
        // التحقق من صحة البيانات القادمة من الواجهة
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable', // 🟢 تم جعله nullable للسماح بالخدمات والفتوكبي الحرة
            'items.*.product_name' => 'required|string|max:255', // لضمان استقبال اسم الخدمة الحرة
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,debt,mixed'
        ]);

        try {
            // تغليف العملية برمتها داخل Transaction للحماية الأمنية للبيانات
            DB::beginTransaction();

            $totalAmount = 0;
            $discount = $request->discount_amount ?? 0;

            // حساب الإجمالي الدقيق من السيرفر (لتجنب تلاعب الواجهة الأمامية)
            foreach ($request->items as $item) {
                $totalAmount += ($item['quantity'] * $item['unit_price']);
            }
            
            $finalTotal = $totalAmount - $discount;

            // إنشاء سجل الفاتورة الرئيسي
            $sale = Sale::create([
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id() ?? 1, // البائع الحالي
                'total_amount' => $finalTotal,
                'paid_amount' => $request->paid_amount,
                'discount_amount' => $discount,
                'payment_method' => $request->payment_method,
            ]);

            // إدخال تفاصيل الفاتورة بشكل آمن ومحمي ضد الـ null
            foreach ($request->items as $item) {
                
                // 🛡️ محرك البحث الآمن عن المنتج لمنع خطأ Attempt to read property on null
                $product = !empty($item['product_id']) ? Product::find($item['product_id']) : null;
                
                $isService = false;
                $realProductId = null;

                if ($product) {
                    $realProductId = $product->id;
                    $isService = isset($product->is_service) ? $product->is_service : false;
                } else {
                    // إذا لم يعثر عليه في قاعدة البيانات، نعتبره خدمة فورية (مثل الفتوكبي)
                    $isService = true;
                    $realProductId = null; // سيتم حقنه كـ null في قاعدة البيانات بأمان
                }

                // إنشاء تفصيل الفاتورة
                $saleItem = $sale->items()->create([
                    'product_id'   => $realProductId,
                    'product_name' => $item['product_name'], // حفظ الاسم الصريح (مثل فتوكبي)
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'subtotal'     => $item['quantity'] * $item['unit_price'],
                ]);

                // 📦 خصم المخزن فقط إذا كان سلعة حقيقية وليس خدمة حرة
                if (!$isService && $product) {
                    $product->decrement('quantity', $item['quantity']);
                }
            }

            // معالجة الديون إذا لم يقم الزبون بدفع المبلغ كاملاً
            if ($request->paid_amount < $finalTotal) {
                if (!$request->customer_id) {
                    throw new Exception("لا يمكن البيع بالدين أو بدفع جزئي لزبون غير مسجل.");
                }
                
                $debtAmount = $finalTotal - $request->paid_amount;
                Customer::where('id', $request->customer_id)->increment('balance', $debtAmount);
            } 
            // معالجة الديون السلبية (الزبون دفع أكثر من المطلوب)
            elseif ($request->paid_amount > $finalTotal && $request->customer_id) {
                $creditAmount = $request->paid_amount - $finalTotal;
                Customer::where('id', $request->customer_id)->decrement('balance', $creditAmount);
            }

            // تأكيد العمليات في قاعدة البيانات
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم حفظ الفاتورة بنجاح',
                'sale_id' => $sale->id
            ], 200);

        } catch (Exception $e) {
            // التراجع عن كل شيء في حال حدوث خطأ لمنع تضارب الحسابات
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء حفظ الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }
}