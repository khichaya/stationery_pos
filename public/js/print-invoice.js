function numberToArabicWords(number) {
    number = Math.floor(Math.abs(parseFloat(number) || 0));
    if (number === 0) return 'صفر';

    const ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
    const teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
    const tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
    const hundreds = ['', 'مائة', 'مئتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];

    function threeDigits(n) {
        let parts = [];
        const h = Math.floor(n / 100);
        const rem = n % 100;

        if (h > 0) parts.push(hundreds[h]);

        if (rem > 0) {
            if (rem < 10) {
                parts.push(ones[rem]);
            } else if (rem < 20) {
                parts.push(teens[rem - 10]);
            } else {
                const t = Math.floor(rem / 10);
                const o = rem % 10;
                if (o > 0) {
                    parts.push(ones[o] + ' و' + tens[t]);
                } else {
                    parts.push(tens[t]);
                }
            }
        }

        return parts.join(' و');
    }

    const groups = [
        { value: 1000000000, singular: 'مليار', dual: 'ملياران', plural: 'مليارات' },
        { value: 1000000, singular: 'مليون', dual: 'مليونان', plural: 'ملايين' },
        { value: 1000, singular: 'ألف', dual: 'ألفان', plural: 'آلاف' },
    ];

    let remaining = number;
    let resultParts = [];

    for (const group of groups) {
        const count = Math.floor(remaining / group.value);
        if (count > 0) {
            if (count === 1) {
                resultParts.push(group.singular);
            } else if (count === 2) {
                resultParts.push(group.dual);
            } else if (count <= 10) {
                resultParts.push(threeDigits(count) + ' ' + group.plural);
            } else {
                resultParts.push(threeDigits(count) + ' ' + group.singular);
            }
            remaining %= group.value;
        }
    }

    if (remaining > 0) {
        resultParts.push(threeDigits(remaining));
    }

    return resultParts.join(' و');
}

function formatAmountInWords(amount) {
    amount = parseFloat(amount) || 0;
    const wholePart = Math.floor(amount);
    const centimes = Math.round((amount - wholePart) * 100);
    const centimesStr = String(centimes).padStart(2, '0');

    return `${numberToArabicWords(wholePart)} دج و ${centimesStr} سنتيم`;
}

function printInvoice(size) {
    const dataEl = document.getElementById('last-sale-data');
    if (!dataEl) { alert('لا توجد فاتورة لطباعتها.'); return; }
    const sale = JSON.parse(dataEl.textContent);

    const isA6 = size === 'A6';
    const isA5 = size === 'A5';
    
    const pageSizes = {
        'A4': { css: '210mm 297mm', width: 800, padding: '15mm', minHeight: '267mm' },
        'A5': { css: '148mm 210mm', width: 560, padding: '10mm', minHeight: '190mm' },
        // ✅ إعطاء عرض 82mm لضمان عدم قطع الأسعار، والطول auto لمنع القص المزدوج
        'A6': { css: '82mm auto', width: 380, padding: '3mm', minHeight: '0' }, 
    };
    const cfg = pageSizes[size] || pageSizes['A4'];

    let rows = '';
    sale.items.forEach((item, index) => {
        rows += `
            <div class="item-row">
                <div class="item-header">
                    <span class="item-name">${item.name}</span>
                    <span class="item-total">${parseFloat(item.subtotal).toFixed(2)}</span>
                </div>
                <div class="item-details">
                    ${item.quantity} × ${parseFloat(item.price).toFixed(2)} دج
                </div>
            </div>`;
    });

    const printWindow = window.open('', '_blank', `width=${cfg.width},height=900`);
    printWindow.document.write(`
        <!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta charset="UTF-8">
            <title>فاتورة مبيعات #${sale.id}</title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap');

                @page { size: ${cfg.css}; margin: 0; }
                * {
                    font-family: 'Tajawal', 'Segoe UI', Tahoma, sans-serif;
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                
                /* ✅ إزالة overflow: hidden لمنع قطع النصوص */
                html, body {
                    width: 100%;
                    height: auto;
                }
                
                body {
                    direction: rtl;
                    margin: 0;
                    padding: ${isA6 ? '3mm' : cfg.padding};
                    color: #000;
                    background: #fff;
                    font-size: ${isA6 ? '10px' : isA5 ? '13px' : '15px'};
                    ${!isA6 ? `min-height: ${cfg.minHeight}; display: flex; flex-direction: column; justify-content: space-between;` : ''}
                }

                /* === الهيدر === */
                .invoice-header { text-align: center; margin-bottom: 8px; }
                
                .logo-img {
                    width: ${isA6 ? '150px' : '200px'} !important; 
                    height: ${isA6 ? '150px' : '200px'} !important;
                    object-fit: contain !important;
                    margin: 0 auto 8px auto !important; 
                    display: block !important;
                }
                
                .company-name { font-size: ${isA6 ? '18px' : '28px'}; font-weight: 900; line-height: 1.2; }
                .company-info { font-size: ${isA6 ? '9px' : '13px'}; color: #333; line-height: 1.5; margin-top: 4px; }
                .tax-info {
                    font-size: ${isA6 ? '8px' : '12px'}; color: #555; margin-top: 6px; padding-top: 6px;
                    border-top: 1px dashed #000; display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;
                }

                /* === معلومات الفاتورة === */
                .invoice-meta {
                    display: flex; justify-content: space-between; font-size: ${isA6 ? '9px' : '13px'};
                    font-weight: 700; border-top: 2px solid #000; border-bottom: 2px solid #000;
                    padding: 4px 0; margin-bottom: 8px;
                }

                /* === قائمة السلع === */
                .item-row { border-bottom: 1px dashed #ccc; padding: 4px 0; }
                
                /* ✅ ضمان بقاء السلعة والسعر في سطر واحد دون قطع */
                .item-header {
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center;
                    font-weight: 700;
                    font-size: ${isA6 ? '11px' : '14px'};
                    gap: 5px;
                }
                .item-name {
                    flex: 1; /* يأخذ المساحة المتبقية */
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis; /* يضع ... إذا كان الاسم طويلاً جداً بدل قطع السعر */
                }
                .item-total {
                    white-space: nowrap; /* يمنع السعر من النزول لسطر جديد */
                }
                
                .item-details { font-size: ${isA6 ? '8px' : '12px'}; color: #666; margin-top: 2px; }

                /* === الإجماليات === */
                .totals-section { border-top: 2px solid #000; margin-top: 8px; padding-top: 8px; }
                .total-row { display: flex; justify-content: space-between; padding: 2px 0; font-size: ${isA6 ? '10px' : '14px'}; }
                .grand-total {
                    display: flex; justify-content: space-between; align-items: center; background: #000; color: #fff;
                    padding: 6px 10px; margin: 8px 0; font-size: ${isA6 ? '15px' : '22px'}; font-weight: 900; border-radius: 4px;
                }
                .words-row {
                    padding: 4px 0; border-top: 1px dashed #999; border-bottom: 1px dashed #999; margin: 8px 0;
                    font-size: ${isA6 ? '9px' : '13px'} !important; font-weight: 700; text-align: center;
                }

                /* === التوقيع === */
                .signature-section { text-align: left; margin-top: ${isA6 ? '12px' : '20px'}; margin-bottom: ${isA6 ? '12px' : '20px'}; }
                .sig-line { border-top: 1px solid #000; padding-top: 4px; font-size: ${isA6 ? '9px' : '13px'}; text-align: center; width: 120px; display: inline-block; }

                /* === الفوتر === */
                .invoice-footer { text-align: center; border-top: 2px dashed #000; padding-top: 8px; }
                .footer-text { font-size: ${isA6 ? '9px' : '13px'}; color: #333; line-height: 1.4; margin-bottom: 6px; font-weight: 600; }
                .thanks { font-size: ${isA6 ? '13px' : '20px'}; font-weight: 900; padding: 6px; border: 1px solid #000; border-radius: 4px; display: inline-block; }
            </style>
        </head>
        <body>

           <div class="invoice-header">
                ${sale.company_logo_base64 ?
                    `<img src="${sale.company_logo_base64}" class="logo-img">` :
                    `<div style="font-size:${isA6 ? '40px' : '50px'}; margin-bottom: 8px;">📚</div>`
                }
                <div class="company-name">${sale.company_name || 'مكتبة السلام'}</div>
                <div class="company-info">
                    ${sale.company_address ? `${sale.company_address}<br>` : ''}
                    ${sale.company_phone ? `هاتف: ${sale.company_phone}` : ''}
                </div>
                <div class="tax-info">
                    ${sale.company_nif ? `<span>NIF: ${sale.company_nif}</span>` : ''}
                    ${sale.company_nis ? `<span>NIS: ${sale.company_nis}</span>` : ''}
                    ${sale.company_rc ? `<span>RC: ${sale.company_rc}</span>` : ''}
                    ${sale.company_ai ? `<span>AI: ${sale.company_ai}</span>` : ''}
                </div>
            </div>

            <div class="invoice-meta">
                <div>فاتورة: #${sale.id}</div>
                <div>${sale.date}</div>
                <div>${sale.customer}</div>
            </div>

            <div class="items-list">
                ${rows}
            </div>

            <div class="totals-section">
                <div class="total-row">
                    <span>الإجمالي الأولي:</span>
                    <span>${parseFloat(sale.total_amount).toFixed(2)} دج</span>
                </div>
                ${sale.discount_amount > 0 ? `
                <div class="total-row" style="color: #c00;">
                    <span>التخفيض:</span>
                    <span>-${parseFloat(sale.discount_amount).toFixed(2)} دج</span>
                </div>
                ` : ''}
                
                <div class="grand-total">
                    <span>الإجمالي المطلوب</span>
                    <span>${parseFloat(sale.final_total).toFixed(2)} دج</span>
                </div>

                ${sale.paid_amount > 0 ? `
                <div class="total-row" style="color: #080; font-weight: 800;">
                    <span>المدفوع كاش:</span>
                    <span>${parseFloat(sale.paid_amount).toFixed(2)} دج</span>
                </div>
                ` : ''}
                ${sale.debt > 0 ? `
                <div class="total-row" style="color: #c00; font-weight: 800;">
                    <span>المتبقي (دين):</span>
                    <span>${parseFloat(sale.debt).toFixed(2)} دج</span>
                </div>
                ` : ''}

                <div class="words-row">
                    أوقفت هذه الفاتورة على مبلغ: ${formatAmountInWords(sale.final_total)}
                </div>
            </div>

            <div class="signature-section">
                <div class="sig-line">التوقيع والختم</div>
            </div>

            <div class="invoice-footer">
                <div class="footer-text">
                    ${sale.company_footer || 'السلع المباعة لا ترد ولا تستبدل بعد 24 ساعة من تاريخ الشراء'}
                </div>
                <div class="thanks">🌟 شكراً لزيارتكم 🌟</div>
            </div>

        </body>
        </html>
    `);
    printWindow.document.close();
}