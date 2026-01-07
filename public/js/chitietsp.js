
        // 1. Đổi ảnh
        function changeImage(element) {
            document.getElementById('mainImage').src = element.src;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            element.classList.add('active');
        }

        // 2. Tăng giảm số lượng
        function increaseQuantity() {
            let qty = document.getElementById('quantity');
            qty.value = parseInt(qty.value) + 1;
        }

        function decreaseQuantity() {
            let qty = document.getElementById('quantity');
            if (parseInt(qty.value) > 1) {
                qty.value = parseInt(qty.value) - 1;
            }
        }
