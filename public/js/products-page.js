// ====== معاينة الصورة ======
function showImagePreview(src, title) {
    document.getElementById('previewImageSrc').src = src;
    document.getElementById('previewImageTitle').textContent = title;
    const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
    modal.show();
}

// ====== طباعة الباركود ======
function printBarcodeTicket(name, price, barcode) {
    const printWindow = window.open('', '_blank', 'width=400,height=300');

    const htmlContent = `
        <!DOCTYPE html>
        <html dir="ltr">
        <head>
            <meta charset="UTF-8">
            <title>Impression étiquette - ${name}</title>
            <style>
                @page { size: 58mm 40mm; margin: 0; }
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body {
                    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
                    text-align: center;
                    width: 58mm;
                    padding: 2mm;
                    font-size: 10px;
                    background: #fff;
                }
                .store-name {
                    font-size: 9px;
                    font-weight: bold;
                    border-bottom: 1px dashed #000;
                    padding-bottom: 2px;
                    margin-bottom: 3px;
                    color: #872061;
                }
                .product-name {
                    font-weight: bold;
                    font-size: 11px;
                    margin-bottom: 2px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    max-width: 54mm;
                }
                .price { font-size: 16px; font-weight: bold; color: #000; margin: 2px 0; }
                .price-currency { font-size: 10px; }
                .barcode-container { margin-top: 3px; display: flex; justify-content: center; }
                .barcode-container svg { max-width: 54mm; height: 30px; }
                .barcode-text { font-family: 'Courier New', monospace; font-size: 9px; letter-spacing: 1px; margin-top: 1px; }
                .date-line { font-size: 7px; color: #666; margin-top: 2px; }
            </style>
        </head>
        <body onload="window.print(); window.close();">
            <div class="store-name">✨ Khaled Auto Pièces ✨</div>
            <div class="product-name">${name}</div>
            <div class="price">
                ${parseFloat(price).toLocaleString('fr-DZ', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                <span class="price-currency">DA</span>
            </div>
            <div class="barcode-container">
                <svg id="barcode-svg"></svg>
            </div>
            <div class="barcode-text">${barcode}</div>
            <div class="date-line">${new Date().toLocaleDateString('fr-FR')}</div>

            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.12.3/dist/JsBarcode.all.min.js"><\/script>
            <script>
                window.onload = function() {
                    try {
                        JsBarcode("#barcode-svg", "${barcode}", {
                            format: "CODE128",
                            width: 1.5,
                            height: 30,
                            displayValue: false,
                            margin: 0,
                            background: "#ffffff",
                            lineColor: "#000000"
                        });
                    } catch(e) {
                        console.error("Barcode error:", e);
                        document.querySelector('.barcode-container').innerHTML =
                            '<div style="font-size:20px;letter-spacing:3px;">| ' + "${barcode}" + ' |</div>';
                    }
                };
            <\/script>
        </body>
        </html>
    `;

    printWindow.document.open();
    printWindow.document.write(htmlContent);
    printWindow.document.close();
}

// ====== محرر النصوص Quill ======
let quillEditor = null;

function isPaneVisible() {
    const pane = document.getElementById('detailsTabPane');
    return pane && !pane.classList.contains('d-none');
}

function initQuill() {
    const el = document.getElementById('productDetailsEditor');
    if (!el || !isPaneVisible()) return;

    // ✅ شرط حاسم لمنع تكرار المحرر: إذا كان المحرر يحتوي بالفعل على كلاس Quill، لا تقم بإنشائه مرة أخرى
    if (el.classList.contains('ql-container')) {
        quillEditor = Quill.find(el);
        return;
    }

    quillEditor = new Quill(el, {
        theme: 'snow',
        placeholder: 'Écrivez les détails du produit ici...',
        modules: {
            toolbar: [
                [{ 'font': [] }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'image'],
                ['clean']
            ],
            clipboard: {
                matchVisual: false
            }
        }
    });

    quillEditor.on('text-change', function() {
        const html = quillEditor.root.innerHTML;
        document.getElementById('detailsContentHidden').value = html;
        
        const wireEl = el.closest('[wire\\:id]');
        if (wireEl) {
            const component = Livewire.find(wireEl.getAttribute('wire:id'));
            if (component) {
                component.set('details_content', html);
            }
        }
    });
}

document.addEventListener('livewire:init', () => {
    const pane = document.getElementById('detailsTabPane');
    if (pane) {
        const observer = new MutationObserver(() => {
            if (isPaneVisible()) { setTimeout(initQuill, 50); }
        });
        observer.observe(pane, { attributes: true, attributeFilter: ['class'] });
    }

    Livewire.hook('morph.updated', () => {
        if (isPaneVisible()) { setTimeout(initQuill, 100); }
    });

    // ✅ استخدام DOM Event بدلاً من Livewire.on لتفادي تكرار الحدث
    window.addEventListener('editor-set-content', (event) => {
        setTimeout(() => {
            if (!quillEditor) initQuill();
            if (quillEditor) { 
                quillEditor.root.innerHTML = event.detail.content || ''; 
            }
        }, 300);
    });

    window.addEventListener('editor-reset', () => {
        if (quillEditor) { quillEditor.root.innerHTML = ''; }
        const hidden = document.getElementById('detailsContentHidden');
        if (hidden) hidden.value = '';
    });

    Livewire.on('close-modal', (event) => {
        const modalElement = document.getElementById(event.modalId);
        if (modalElement) {
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) { modalInstance.hide(); }
        }
    });
});