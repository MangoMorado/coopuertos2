import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Importar CropperJS
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.min.css';

// Hacer disponible globalmente para usar en otros scripts
window.Cropper = Cropper;
