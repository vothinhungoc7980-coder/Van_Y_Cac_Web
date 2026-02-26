async function fetchAPI(action, method = 'GET', data = null) {
    try {
        const response = await fetch(`public/api.php?action=${action}`, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: data ? JSON.stringify(data) : null
        });

        return await response.json();

    } catch (error) {
        console.error("Lỗi API:", error);
        return { success: false, message: "Không thể kết nối server!" };
    }
}