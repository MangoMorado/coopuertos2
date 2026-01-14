<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma de Permisos
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan cuando un usuario no tiene
    | los permisos necesarios para realizar una acción. Puedes modificar
    | estas líneas de idioma según los requisitos de tu aplicación.
    |
    */

    'denied' => 'El usuario no tiene los permisos necesarios.',
    'unauthorized' => 'No tienes permiso para realizar esta acción.',
    'insufficient_permissions' => 'No tienes los permisos suficientes para acceder a este recurso.',

    // Traducciones para Spatie Permission
    'User does not have the right roles.' => 'El usuario no tiene los roles necesarios.',
    'User does not have the right permissions.' => 'El usuario no tiene los permisos necesarios.',
    'User does not have any of the necessary access rights.' => 'El usuario no tiene ninguno de los derechos de acceso necesarios.',
    'Necessary roles are :roles' => 'Los roles necesarios son: :roles',
    'Necessary permissions are :permissions' => 'Los permisos necesarios son: :permissions',
    'Necessary roles or permissions are :values' => 'Los roles o permisos necesarios son: :values',
    'User is not logged in.' => 'El usuario no ha iniciado sesión.',
    'Authorizable class `:class` must use Spatie\\Permission\\Traits\\HasRoles trait.' => 'La clase autorizable `:class` debe usar el trait Spatie\\Permission\\Traits\\HasRoles.',

];
