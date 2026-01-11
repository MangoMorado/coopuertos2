import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Importar componentes de toast
import { toastContainer } from './alpine-toast';
import { toastNotification } from './alpine-toast';
import * as toastUtils from './toast';

// Registrar componentes Alpine
Alpine.data('toastContainer', toastContainer);
Alpine.data('toastNotification', toastNotification);

// Store global para el estado del sidebar
Alpine.store('sidebar', {
    collapsed: false, // Por defecto expandido en desktop
});

// Store global para el tema
Alpine.store('theme', {
    current: window.__currentTheme || 'light',
    
    toggle() {
        const newTheme = this.current === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    },
    
    setTheme(theme) {
        if (theme !== 'light' && theme !== 'dark') {
            return;
        }
        
        this.current = theme;
        
        // Aplicar tema al elemento html
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        // Guardar en localStorage
        localStorage.setItem('theme', theme);
        
        // Sincronizar con servidor mediante API
        this.syncWithServer(theme);
    },
    
    async syncWithServer(theme) {
        try {
            const response = await fetch('/api/theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ theme }),
            });
            
            if (!response.ok) {
                console.warn('No se pudo sincronizar el tema con el servidor');
            }
        } catch (error) {
            console.warn('Error al sincronizar tema:', error);
        }
    },
});

// Hacer disponibles las funciones de toast globalmente
window.initToastContainer = toastUtils.initToastContainer;
window.initSessionMessages = toastUtils.initSessionMessages;

Alpine.start();

// Importar CropperJS
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.min.css';

// Hacer disponible globalmente para usar en otros scripts
window.Cropper = Cropper;

// Importar Chart.js
import {
    Chart,
    ArcElement,
    PieController,
    Tooltip,
    Legend
} from 'chart.js';

// Registrar componentes necesarios para gráfico de pastel
Chart.register(ArcElement, PieController, Tooltip, Legend);

// Hacer Chart disponible globalmente
window.Chart = Chart;
