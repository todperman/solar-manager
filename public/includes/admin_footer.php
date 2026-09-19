        </div><!-- .page-content -->
    </main>
</div>
<script>
// Mobile sidebar toggle
if (window.innerWidth <= 768) {
    document.getElementById('menuBtn').style.display = 'block';
}
window.addEventListener('resize', () => {
    document.getElementById('menuBtn').style.display = window.innerWidth <= 768 ? 'block' : 'none';
});

// Modal functions
function openModal(id) {
    document.getElementById(id).classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// Delete confirm
function confirmDelete(url) {
    if (confirm('คุณต้องการลบรายการนี้ใช่หรือไม่?')) {
        window.location.href = url;
    }
}

// Flash auto-dismiss
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => {
        el.style.transition = 'opacity .3s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    });
}, 4000);
</script>
</body>
</html>
