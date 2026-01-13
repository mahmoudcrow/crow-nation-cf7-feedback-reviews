document.addEventListener('DOMContentLoaded', function () {
    // زر تحميل الكارت كصورة
    document.querySelectorAll('.download-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.getAttribute('data-id');
            const card = document.getElementById('cf7fr-card-' + id);

            if (!card) return;

            html2canvas(card, {
                backgroundColor: '#ffffff',
                scale: 2
            }).then(function (canvas) {
                const link = document.createElement('a');
                link.download = 'review-' + id + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        });
    });

    html2canvas(card, {
        backgroundColor: '#ffffff',
        scale: 2
    }).then(function (canvas) {
        const link = document.createElement('a');
        link.download = 'review-' + id + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
});