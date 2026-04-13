// DATOS
const fotosTrabajos = ["img/carusel2.png", "img/carusel2.2.png", "img/carusel3.png"];
const productos = [
    { id: 1, nombre: "Ceramica Estratificada", precio: 120, img:"img/CERÁMICA ESTRATIFICADA/ceramica2.png" },
    { id: 2, nombre: "Disilicato", precio: 250, img: "img/DISILICATO/disilicato4.png" },
    { id: 3, nombre: "Zirconio", precio: 60, img: "img/ZIRCONIO/zirconio1.png" }
];

let carrito = [];

// RENDERIZAR GALERÍA
function renderGaleria() {
    const galeria = document.getElementById("galeriaContainer");
    if (!galeria) return;
    galeria.innerHTML = fotosTrabajos.map(foto => `
        <div class="col-md-4">
            <div class="card-custom overflow-hidden">
                <img src="${foto}" class="w-100" style="height:250px; object-fit:cover;">
            </div>
        </div>
    `).join('');
}

// RENDERIZAR PRODUCTOS
function renderProductos() {
    const container = document.getElementById("productosContainer");
    if (!container) return;
    container.innerHTML = productos.map(p => `
        <div class="col-md-4">
            <div class="card card-custom p-3 text-center">
                <img src="${p.img}" class="rounded mb-3">
                <h5>${p.nombre}</h5>
                <p class="fw-bold">$${p.precio}</p>
                <button class="btn btn-bordeo" onclick="agregarCarrito(${p.id})">Agregar</button>
            </div>
        </div>
    `).join('');
}

// LÓGICA CARRITO
function agregarCarrito(id) {
    const prod = productos.find(p => p.id === id);
    const item = carrito.find(p => p.id === id);
    item ? item.cantidad++ : carrito.push({...prod, cantidad: 1});
    renderCarrito();
}

function eliminarProducto(id) {
    carrito = carrito.filter(p => p.id !== id);
    renderCarrito();
}

function renderCarrito() {
    const container = document.getElementById("carrito");
    if (!container) return;
    if (carrito.length === 0) { container.innerHTML = "<p>Vacío</p>"; return; }

    let total = 0;
    let html = `<table class="table"><thead><tr><th>Producto</th><th>Precio</th><th>Cant.</th><th>Subtotal</th><th></th></tr></thead><tbody>`;
    
    carrito.forEach(item => {
        const sub = item.precio * item.cantidad;
        total += sub;
        html += `<tr><td>${item.nombre}</td><td>$${item.precio}</td><td>${item.cantidad}</td><td>$${sub}</td>
                 <td><button class="btn btn-sm btn-danger" onclick="eliminarProducto(${item.id})">x</button></td></tr>`;
    });

    html += `</tbody><tfoot><tr><th colspan="3">Total</th><th>$${total}</th><th></th></tr></tfoot></table>`;
    container.innerHTML = html;
}

function comprarTodo() {
    if (carrito.length === 0) return alert("Carrito vacío");
    alert("¡Pedido realizado con éxito!");
    carrito = [];
    renderCarrito();
}

// INICIO
document.addEventListener("DOMContentLoaded", () => {
    renderGaleria();
    renderProductos();
});