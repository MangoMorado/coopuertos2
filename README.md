# Coopuertos - Sistema de Gestión de Cooperativa de Puertos

Sistema web desarrollado con Laravel 12 para la gestión integral de conductores, vehículos, propietarios y PQRS (Peticiones, Quejas, Reclamos y Sugerencias) de una cooperativa de puertos.

## 🚀 Características Principales

### Gestión de Conductores
- CRUD completo de conductores
- Generación de carnets con QR
- Fotos públicas de conductores
- Búsqueda en tiempo real
- Visualización pública mediante UUID

### Gestión de Vehículos
- CRUD completo de vehículos
- Asociación con conductores
- Búsqueda avanzada de vehículos
- Gestión de placas

### Gestión de Propietarios
- CRUD completo de propietarios
- Búsqueda de propietarios
- Asociación con vehículos

### Sistema de PQRS
- Formularios públicos para PQRS de servicio
- Formularios de PQRS para taquilla
- Editor visual de formularios
- Gestión de estados de PQRS
- Generación de códigos QR para formularios
- Sistema de adjuntos

### Sistema de Carnets
- Diseñador visual de plantillas de carnets
- Personalización de carnets
- Generación masiva de carnets
- Descarga en formato ZIP
- Seguimiento de progreso de generación

### Interfaz de Usuario
- Tema claro/oscuro
- Sidebar de navegación
- Dashboard con métricas
- UI completamente en español
- Diseño responsive

## 📋 Requisitos

- PHP >= 8.2
- Composer
- Node.js y NPM
- Base de datos (MySQL, PostgreSQL, SQLite)
- Servidor web (Apache/Nginx) o PHP Built-in Server

## 🛠️ Instalación

1. Clonar el repositorio:
```bash
git clone [url-del-repositorio]
cd coopuertos2
```

2. Instalar dependencias de PHP:
```bash
composer install
```

3. Instalar dependencias de Node.js:
```bash
npm install
```

4. Configurar el archivo de entorno:
```bash
cp .env.example .env
php artisan key:generate
```

5. Configurar la base de datos en `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coopuertos
DB_USERNAME=root
DB_PASSWORD=
```

6. Ejecutar migraciones:
```bash
php artisan migrate
```

7. (Opcional) Poblar la base de datos con datos de prueba:
```bash
php artisan db:seed
```

8. Compilar assets:
```bash
npm run build
```

9. Iniciar el servidor de desarrollo:
```bash
php artisan serve
```

O usar el script de desarrollo que incluye servidor, cola de trabajos y logs:
```bash
composer run dev
```

## 📦 Tecnologías Utilizadas

- **Backend**: Laravel 12
- **Frontend**: Blade Templates, Tailwind CSS, Alpine.js
- **Generación de PDFs**: DomPDF
- **Generación de QR**: SimpleSoftwareIO/simple-qrcode
- **Autenticación**: Laravel Breeze
- **Roles y Permisos**: Spatie Laravel Permission
- **Base de datos**: MySQL/PostgreSQL/SQLite

## 📁 Estructura del Proyecto

```
coopuertos2/
├── app/
│   ├── Http/Controllers/    # Controladores
│   ├── Models/              # Modelos Eloquent
│   ├── Jobs/                # Trabajos en cola
│   └── Console/Commands/    # Comandos Artisan
├── database/
│   ├── migrations/          # Migraciones de base de datos
│   └── seeders/             # Seeders
├── resources/
│   ├── views/               # Vistas Blade
│   └── js/                  # JavaScript
├── routes/
│   ├── web.php              # Rutas web
│   └── auth.php             # Rutas de autenticación
└── public/                  # Archivos públicos
```

## 🚦 Comandos Útiles

- **Desarrollo**: `composer run dev` - Inicia servidor, cola y logs
- **Tests**: `composer run test` - Ejecuta las pruebas
- **Setup completo**: `composer run setup` - Instalación completa

## 🔐 Sistema de Roles y Permisos

El sistema utiliza **Spatie Laravel Permission** para gestionar roles y permisos de manera granular.

### Roles Disponibles

- **Mango**: Rol SuperAdmin con acceso completo a todos los módulos y configuración
- **Admin**: Rol administrativo con acceso a todos los módulos excepto configuración
- **User**: Rol de usuario básico con acceso de solo lectura

### Módulos y Permisos

Cada módulo tiene 4 permisos base que controlan las acciones:

- `ver {modulo}`: Ver/Listar elementos del módulo
- `crear {modulo}`: Crear nuevos elementos
- `editar {modulo}`: Editar elementos existentes
- `eliminar {modulo}`: Eliminar elementos

#### Módulos Disponibles

1. **Dashboard** (`dashboard`)
   - Ver panel de control y estadísticas

2. **Conductores** (`conductores`)
   - Ver, crear, editar y eliminar conductores
   - Generar carnets
   - Ver información detallada

3. **Vehículos** (`vehiculos`)
   - Ver, crear, editar y eliminar vehículos
   - Asociar con conductores

4. **Propietarios** (`propietarios`)
   - Ver, crear, editar y eliminar propietarios

5. **Carnets** (`carnets`)
   - Ver, crear, editar y eliminar carnets
   - Personalizar plantillas
   - Generar carnets masivos

6. **Configuración** (`configuracion`) - Solo para rol Mango
   - Gestionar permisos por módulo y rol
   - Configurar acceso de usuarios

### Configuración de Permisos

- Los módulos aparecen automáticamente en el navbar según los permisos del usuario
- El rol Mango tiene acceso completo y no puede ser modificado
- Los permisos se pueden gestionar desde la vista de Configuración (solo Mango)
- Los permisos se aplican tanto en rutas como en vistas mediante directivas `@can` y middleware

## 📝 Notas de Versión

Para ver el historial completo de cambios y mejoras, consulta el archivo [changenotes.md](changenotes.md).

## 📝 Roadmap

Para ver el progreso de la App y su bitacora de cambios, consulta el archivo [roadmap.md](roadmap.md).

## 📄 Licencia

Este proyecto es software propietario. Todos los derechos reservados.

## 👥 Soporte

Para soporte técnico o consultas, contactar al equipo de desarrollo.
