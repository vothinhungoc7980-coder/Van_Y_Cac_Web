
        // --- JAVASCRIPT XỬ LÝ TÍNH TIỀN ---
        
        const checkAll = document.getElementById('checkAll');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const totalMoneyEl = document.getElementById('total-money');
        const selectedCountEl = document.getElementById('selected-count');
        const btnBuy = document.getElementById('btn-buy');

        // Hàm định dạng tiền Việt Nam
        function formatCurrency(amount) {
            return amount.toLocaleString('vi-VN') + '₫';
        }

        // Hàm tính lại tổng tiền
        function recalculateTotal() {
            let total = 0;
            let count = 0;

            itemCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    // Lấy giá trị tiền từ data-price
                    total += parseInt(checkbox.getAttribute('data-price'));
                    count++;
                }
            });

            // Cập nhật giao diện
            totalMoneyEl.innerText = formatCurrency(total);
            selectedCountEl.innerText = count + " món";

            // Nếu không chọn món nào thì làm mờ nút Mua
            if (count === 0) {
                btnBuy.disabled = true;
                btnBuy.innerText = "CHƯA CHỌN MÓN NÀO";
            } else {
                btnBuy.disabled = false;
                btnBuy.innerText = "MUA HÀNG (" + count + ")";
            }
            
            // Kiểm tra nút "Chọn tất cả"
            checkAll.checked = (count === itemCheckboxes.length && count > 0);
        }

        // Sự kiện: Bấm "Chọn tất cả"
        if(checkAll) {
            checkAll.addEventListener('change', function() {
                itemCheckboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                recalculateTotal();
            });
        }

        // Sự kiện: Bấm từng món
        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', recalculateTotal);
        });

        // Chạy tính toán lần đầu khi load trang
        recalculateTotal();

        // Xử lý nút MUA HÀNG (Demo)
        btnBuy.addEventListener('click', function() {
            // Logic lấy các ID đã chọn để gửi đi thanh toán
            let selectedIds = [];
            itemCheckboxes.forEach(cb => {
                if(cb.checked) selectedIds.push(cb.value);
            });
            alert("Bạn đang thanh toán cho các sản phẩm có mã: " + selectedIds.join(", "));
            // Ở đây bạn có thể submit form thật: document.getElementById('checkout-form').submit();
        });