# Proyecto UDAI - PHP 8 + MariaDB + Bootstrap 5

Este es un scaffold (plantilla) mínima para una aplicación web pública en PHP 8 con MariaDB y Bootstrap 5.
Incluye:
- Esquema SQL (tablas que proporcionaste).
- Conexión PDO.
- Autenticación básica (registro / login / logout).
- CRUD de ejemplo para `asignatura`.
- Layout con Bootstrap 5 (CDN).

Requisitos
- PHP 8.0+
- MariaDB o MySQL
- Servidor web (Apache/Nginx) con webroot apuntando a la carpeta `public/`.

Instalación rápida
1. Clona el repositorio o copia los archivos.
2. Crea la base de datos en MariaDB:
   - `mysql -u root -p`
   - `CREATE DATABASE udai CHARACTER SET latin1 COLLATE latin1_swedish_ci;`
   - `USE udai;`
   - Importa el esquema: `SOURCE db/schema.sql;`
3. Configura `app/config.php` con tu host, usuario, contraseña y nombre de base de datos.
4. Configura el webroot de tu servidor al directorio `public/`.
5. Asegúrate de que `sessions` funciona y que PHP puede escribir si usas archivos subidos u otro almacenamiento.
6. Abre `http://tu-dominio/` y regístrate para crear el primer usuario.

Notas de seguridad y siguientes pasos
- En producción configura HTTPS y variables de entorno para credenciales.
- Limita registros públicos o agrega verificación de correo si hace falta.
- Añadir CSRF tokens en formularios y validaciones más estrictas de inputs.
- Implementar roles y permisos (campo `rol` en `usuarios` ya existe).
- Extender CRUD para las demás tablas (estudiante, docente, registro_nee, etc.). Puedo generarlos si lo deseas.

Sigo: dime si quieres que haga push a tu repo (necesito confirmación de owner/branch) o que genere CRUDs para otras tablas.