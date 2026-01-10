import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Store global para el estado del sidebar
// Usar estado inicial desde script inline si está disponible (para evitar flash)
Alpine.store('sidebar', {
    collapsed: window.__sidebarInitialState?.collapsed ?? (window.innerWidth < 768),
    mobileOpen: window.__sidebarInitialState?.mobileOpen ?? false,
});

Alpine.start();

// Importar CropperJS
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.min.css';

// Hacer disponible globalmente para usar en otros scripts
window.Cropper = Cropper;
