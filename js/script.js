function togglePassword() {
    const input = document.getElementById('password');
    const btn = document.querySelector('.toggle-password');
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? '👁' : '🙈';
}

function togglePass(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? '👁' : '🙈';
}

function medirFuerza(password) {
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let puntos = 0;
    if (password.length >= 8)           puntos++;
    if (password.length >= 12)          puntos++;
    if (/[A-Z]/.test(password))         puntos++;
    if (/[0-9]/.test(password))         puntos++;
    if (/[^A-Za-z0-9]/.test(password)) puntos++;
    const niveles = [
        { pct: '0%',   color: 'transparent', label: '' },
        { pct: '25%',  color: '#dc3545',     label: '⚠️ Muy débil' },
        { pct: '50%',  color: '#fd7e14',     label: '🔶 Débil' },
        { pct: '75%',  color: '#ffc107',     label: '🔷 Aceptable' },
        { pct: '90%',  color: '#20c997',     label: '✅ Fuerte' },
        { pct: '100%', color: '#198754',     label: '🔒 Muy fuerte' },
    ];
    const nivel = niveles[Math.min(puntos, 5)];
    bar.style.width      = password.length > 0 ? nivel.pct : '0%';
    bar.style.background = nivel.color;
    text.textContent     = password.length > 0 ? nivel.label : '';
    text.style.color     = nivel.color;
}

function toggleDetalle(id) {
    const fila = document.getElementById('fila-' + id);
    const detalle = document.getElementById('detalle-' + id);
    if (detalle.style.display !== 'none') {
        detalle.style.display = 'none';
        fila.classList.remove('abierta');
    } else {
        detalle.style.display = 'table-row';
        fila.classList.add('abierta');
    }
}