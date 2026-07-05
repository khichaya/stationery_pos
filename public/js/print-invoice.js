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

// 🆕 يجمع الجزء الصحيح بالأحرف + "دج" + السنتيمات كأرقام (مو بالأحرف)
// مثال: 2650.03 -> "ألفان وستمائة وخمسون دج و 03 سنتيم"
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

    const pageSizes = {
        'A4': { css: '210mm 297mm', width: 800, padding: '20mm', minHeight: '257mm' },
        'A5': { css: '148mm 210mm', width: 560, padding: '15mm', minHeight: '180mm' },
        'A6': { css: '105mm 148mm', width: 380, padding: '8mm', minHeight: '132mm' },
    };
    const cfg = pageSizes[size] || pageSizes['A4'];

    let rows = '';
    sale.items.forEach((item, index) => {
        rows += `
            <tr>
                <td style="text-align:center; width:10%; border:1px solid #333; padding:6px;">${index + 1}</td>
                <td style="text-align:right; border:1px solid #333; padding:6px;">${item.name}${item.is_custom ? ' <span style="font-size:0.85em;">(يدوي)</span>' : ''}</td>
                <td style="text-align:center; width:12%; border:1px solid #333; padding:6px;">${item.quantity}</td>
                <td style="text-align:center; width:15%; border:1px solid #333; padding:6px;">${parseFloat(item.price).toFixed(2)}</td>
                <td style="text-align:center; width:18%; border:1px solid #333; padding:6px; font-weight:bold;">${parseFloat(item.subtotal).toFixed(2)}</td>
            </tr>`;
    });

    const minRows = size === 'A4' ? 12 : size === 'A5' ? 8 : 5;
    for (let i = sale.items.length; i < minRows; i++) {
        rows += `
            <tr>
                <td style="border:1px solid #333; padding:6px;">&nbsp;</td>
                <td style="border:1px solid #333; padding:6px;">&nbsp;</td>
                <td style="border:1px solid #333; padding:6px;">&nbsp;</td>
                <td style="border:1px solid #333; padding:6px;">&nbsp;</td>
                <td style="border:1px solid #333; padding:6px;">&nbsp;</td>
            </tr>`;
    }

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
                }
                body {
                    direction: rtl;
                    margin: 0;
                    padding: ${cfg.padding};
                    color: #000;
                    background: #fff;
                    font-size: ${size === 'A6' ? '10px' : size === 'A5' ? '11px' : '13px'};
                    min-height: ${cfg.minHeight};
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }

                .invoice-header {
                    text-align: center;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                    margin-bottom: 12px;
                }
                .invoice-header .logo-section {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 12px;
                    margin-bottom: 6px;
                }
                .invoice-header .logo-img {
                    width: ${size === 'A6' ? '45px' : '60px'};
                    height: ${size === 'A6' ? '45px' : '60px'};
                    object-fit: contain;
                }
                .invoice-header .company-name {
                    font-size: ${size === 'A6' ? '18px' : size === 'A5' ? '22px' : '28px'};
                    font-weight: 900;
                    color: #000;
                    margin: 0;
                    letter-spacing: 1px;
                }
                .invoice-header .company-info {
                    font-size: ${size === 'A6' ? '9px' : '11px'};
                    color: #333;
                    margin-top: 3px;
                    line-height: 1.5;
                }
                .invoice-header .tax-info {
                    font-size: ${size === 'A6' ? '8px' : '10px'};
                    color: #555;
                    margin-top: 5px;
                    padding-top: 5px;
                    border-top: 1px dashed #999;
                }

                .invoice-meta {
                    display: flex;
                    justify-content: space-between;
                    background: #f5f5f5;
                    padding: 8px 10px;
                    border: 1px solid #333;
                    margin-bottom: 12px;
                }
                .invoice-meta .meta-item {
                    font-size: ${size === 'A6' ? '9px' : '11px'};
                }
                .invoice-meta .meta-label {
                    font-weight: 800;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 12px;
                    flex-grow: 1;
                }
                thead {
                    background: #e0e0e0;
                }
                th {
                    border: 1px solid #333;
                    padding: ${size === 'A6' ? '5px' : '8px'};
                    text-align: center;
                    font-weight: 800;
                    font-size: ${size === 'A6' ? '9px' : '11px'};
                }
                td {
                    border: 1px solid #333;
                    padding: ${size === 'A6' ? '4px' : '6px'};
                }

                .totals-section {
                    border: 2px solid #000;
                    padding: 10px;
                    margin-top: 10px;
                    background: #fafafa;
                }
                .totals-section .total-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 3px 0;
                    font-size: ${size === 'A6' ? '10px' : '12px'};
                }
                .totals-section .grand-total {
                    display: flex;
                    justify-content: space-between;
                    padding: 6px 0;
                    margin-top: 4px;
                    border-top: 2px solid #000;
                    font-size: ${size === 'A6' ? '14px' : size === 'A5' ? '16px' : '20px'};
                    font-weight: 900;
                }
                .totals-section .debt-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 3px 0;
                    color: #c00;
                    font-weight: 800;
                    font-size: ${size === 'A6' ? '10px' : '12px'};
                }
                .totals-section .paid-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 3px 0;
                    color: #080;
                    font-weight: 800;
                }

                .signature-section {
                    margin-top: 20px;
                    text-align: left;
                }
                .signature-section .sig-box {
                    display: inline-block;
                    text-align: center;
                    width: 150px;
                }
                .signature-section .sig-line {
                    border-top: 1px solid #000;
                    margin-top: 30px;
                    padding-top: 4px;
                    font-size: ${size === 'A6' ? '9px' : '11px'};
                }

                .invoice-footer {
                    margin-top: 15px;
                    text-align: center;
                    padding: 10px;
                    border: 1px solid #333;
                    background: #f9f9f9;
                }
                .invoice-footer .footer-text {
                    font-size: ${size === 'A6' ? '9px' : '11px'};
                    color: #333;
                    line-height: 1.6;
                }
                .invoice-footer .thanks {
                    font-size: ${size === 'A6' ? '11px' : '13px'};
                    font-weight: 800;
                    margin-top: 6px;
                    color: #000;
                }
            </style>
        </head>
        <body>

           <div class="invoice-header">
                <div class="logo-section" style="display:flex; align-items:center; justify-content:center; gap:15px; margin-bottom:8px;">
                    ${sale.company_logo_base64 ?
                        `<img src="${sale.company_logo_base64}"
                              style="width:${size === 'A6' ? '50px' : '70px'};
                                     height:${size === 'A6' ? '50px' : '70px'};
                                     object-fit:contain;
                                     border-radius:6px;">` :
                        `<div style="font-size:${size === 'A6' ? '35px' : '50px'};">📚</div>`
                    }
                    <div>
                        <div class="company-name" style="font-size:${size === 'A6' ? '18px' : size === 'A5' ? '22px' : '28px'}; font-weight:900; color:#000;">${sale.company_name || 'مكتبة السلام'}</div>
                        <div style="font-size:${size === 'A6' ? '9px' : '11px'}; color:#333; margin-top:3px; line-height:1.5;">
                            ${sale.company_address ? `📍 ${sale.company_address}<br>` : ''}
                            ${sale.company_phone ? `📞 ${sale.company_phone}` : ''}
                        </div>
                    </div>
                </div>
                <div style="font-size:${size === 'A6' ? '8px' : '10px'}; color:#555; margin-top:5px; padding-top:5px; border-top:1px dashed #999;">
                    ${sale.company_nif ? `<strong>NIF:</strong> ${sale.company_nif} | ` : ''}
                    ${sale.company_nis ? `<strong>NIS:</strong> ${sale.company_nis} | ` : ''}
                    ${sale.company_rc ? `<strong>RC:</strong> ${sale.company_rc} | ` : ''}
                    ${sale.company_ai ? `<strong>Art:</strong> ${sale.company_ai}` : ''}
                </div>
            </div>

            <div class="invoice-meta">
                <div class="meta-item">
                    <span class="meta-label">فاتورة رقم:</span> #${sale.id}
                </div>
                <div class="meta-item">
                    <span class="meta-label">التاريخ:</span> ${sale.date}
                </div>
                <div class="meta-item">
                    <span class="meta-label">الزبون:</span> ${sale.customer}
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>البيان / الوصف</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows}
                </tbody>
            </table>

            <div class="totals-section">
                <div class="total-row">
                    <span>الإجمالي الأولي:</span>
                    <span style="font-weight:600;">${parseFloat(sale.total_amount).toFixed(2)} دج</span>
                </div>
                <div class="total-row">
                    <span>التخفيض التجاري:</span>
                    <span style="color:#c00;">-${parseFloat(sale.discount_amount).toFixed(2)} دج</span>
                </div>
                <div class="grand-total">
                    <span>الصافي الإجمالي</span>
                    <span>${parseFloat(sale.final_total).toFixed(2)} دج</span>
                </div>
                ${sale.paid_amount > 0 ? `
                <div class="paid-row">
                    <span>المبلغ المدفوع كاش:</span>
                    <span>${parseFloat(sale.paid_amount).toFixed(2)} دج</span>
                </div>
                ` : ''}
                <div class="words-row" style="padding: 6px 0; border-top: 1px dashed #999; margin-top: 4px; font-size: ${size === 'A6' ? '9px' : '11px'}; font-weight: 700;">
                    أوقفت هذه الفاتورة على مبلغ: ${formatAmountInWords(sale.final_total)}
                </div>
                ${sale.debt > 0 ? `
                <div class="debt-row">
                    <span>المبلغ المتبقي (دين):</span>
                    <span>${parseFloat(sale.debt).toFixed(2)} دج</span>
                </div>
                ` : ''}
            </div>

            <div class="signature-section">
                <div class="sig-box">
                    <div class="sig-line">التوقيع والختم</div>
                </div>
            </div>

            <div class="invoice-footer">
                <div class="footer-text">
                    ${sale.company_footer || 'السلع المباعة لا ترد ولا تستبدل بعد 24 ساعة من تاريخ الشراء'}
                </div>
                <div class="thanks">🌟 ${sale.company_name || 'مكتبة الــــــــــــــسلام'} 🌟</div>
            </div>

        </body>
        </html>
    `);
    printWindow.document.close();
}