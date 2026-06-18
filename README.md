# Sistema de Gestion de Cloro Residual

Aplicacion web en PHP y MySQL para registrar, consultar y administrar informacion de centros poblados, JASS, municipalidades, sistemas de agua, fichas de control de cloro residual y solicitudes de asistencia tecnica.

El sistema esta orientado al seguimiento del abastecimiento de agua y saneamiento en el ambito rural, usando una interfaz tipo DATASS con autenticacion por roles.

## Tabla de contenido

- [Caracteristicas principales](#caracteristicas-principales)
- [Tecnologias utilizadas](#tecnologias-utilizadas)
- [Requisitos](#requisitos)
- [Instalacion y ejecucion local](#instalacion-y-ejecucion-local)
- [Configuracion de base de datos](#configuracion-de-base-de-datos)
- [Acceso al sistema](#acceso-al-sistema)
- [Roles y permisos](#roles-y-permisos)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Modulos del sistema](#modulos-del-sistema)
- [Flujo de trabajo recomendado](#flujo-de-trabajo-recomendado)
- [Seguridad aplicada](#seguridad-aplicada)
- [Solucion de problemas](#solucion-de-problemas)

## Caracteristicas principales

- Inicio de sesion con verificacion de credenciales.
- Control de acceso por roles: Administrador, Municipalidad y Gobierno Regional.
- Dashboard con resumen de centros poblados, municipalidades, JASS, sistemas de agua y fichas de cloracion.
- Mantenimiento de centros poblados con busqueda, registro, edicion, eliminacion, detalle y cuestionario.
- Mantenimiento de municipalidades, JASS y sistemas de agua.
- Registro de fichas tecnicas de control de cloro residual.
- Carga opcional de archivos PDF para fichas tecnicas.
- Solicitud y gestion de asistencias tecnicas.
- Proteccion CSRF en formularios principales.
- Escapado de salida HTML mediante helper `e()`.
- Uso de consultas preparadas con `mysqli`.

## Tecnologias utilizadas

- PHP 8.x
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- WAMP Server
- phpMyAdmin

## Requisitos

Para ejecutar el proyecto localmente se recomienda:

- WAMP Server, XAMPP o entorno equivalente.
- PHP 8.0 o superior.
- MySQL 8.x o MariaDB compatible.
- Navegador web moderno.
- Git, opcional para clonar el repositorio.

## Instalacion y ejecucion local

1. Clonar el repositorio o copiar el proyecto dentro del directorio web de WAMP:

   ```bash
   git clone https://github.com/UPT-FAING-EPIS/lab-2026-i-si784-u2-01-cs-diegochara2019065026.git sistema
   ```

2. Ubicar la carpeta del proyecto en:

   ```text
   C:\wamp64\www\sistema
   ```

3. Iniciar los servicios de WAMP:

   - Apache
   - MySQL

4. Crear e importar la base de datos desde phpMyAdmin.

5. Abrir el sistema en el navegador:

   ```text
   http://localhost/sistema/login.php
   ```

## Configuracion de base de datos

El archivo de conexion principal es:

```text
conexion.php
```

La configuracion actual usa los valores por defecto de WAMP:

```php
$host = "localhost";
$usuario = "root";
$password = "";
$base_de_datos = "sgmcr";
```

Si tu MySQL tiene contrasena, modifica `$password` en `conexion.php`.

### Importar la base de datos

1. Abrir phpMyAdmin:

   ```text
   http://localhost/phpmyadmin
   ```

2. Crear una base de datos llamada:

   ```text
   sgmcr
   ```

3. Seleccionar la base de datos `sgmcr`.

4. Ir a la pestana **Importar**.

5. Seleccionar el archivo:

   ```text
   data/sgmcr (1).sql
   ```

6. Ejecutar la importacion.

## Acceso al sistema

El sistema inicia en:

```text
http://localhost/sistema/login.php
```

La autenticacion usa `password_verify()`, por lo que las contrasenas guardadas deben estar cifradas con `password_hash()`.

Si necesitas crear un usuario administrador inicial, puedes generar un hash desde la terminal:

```bash
php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
```

Luego inserta el usuario en la tabla `usuarios`, usando el hash generado:

```sql
INSERT INTO usuarios (nombre, correo, contrasena, rol_id, estado)
VALUES ('Administrador', 'admin@example.com', 'PEGA_AQUI_EL_HASH_GENERADO', 1, 1);
```

Despues podras iniciar sesion con:

- Correo: `admin@example.com`
- Contrasena: `admin123`

> Recomendacion: cambia estos datos despues del primer ingreso.

## Roles y permisos

El sistema define los siguientes roles:

| Rol | Descripcion | Accesos principales |
| --- | --- | --- |
| Administrador | Gestion general del sistema | Dashboard, usuarios, mantenimientos, solicitudes y gestion de asistencias |
| Municipalidad | Usuario municipal | Dashboard y solicitud de asistencia tecnica |
| Gobierno Regional | Usuario tecnico/regional | Dashboard y gestion/programacion de asistencias tecnicas |

Los permisos se controlan desde `includes/helpers.php` con funciones como:

- `require_login()`
- `require_admin()`
- `require_any_role()`
- `has_role()`
- `has_any_role()`

## Estructura del proyecto

```text
sistema/
|-- conexion.php
|-- login.php
|-- logout.php
|-- dashboard.php
|-- crear_usuario.php
|-- mantenimiento_centros.php
|-- cuestionario_centro.php
|-- detalle_centro.php
|-- mantenimiento_municipalidades.php
|-- mantenimiento_jass.php
|-- mantenimiento_sistemas_agua.php
|-- mantenimiento_ficha.php
|-- solicitar_asistencia.php
|-- gestionar_asistencia.php
|-- dashboard.css
|-- login.css
|-- registro.css
|-- includes/
|   `-- helpers.php
|-- data/
|   `-- sgmcr (1).sql
`-- uploads/
    `-- fichas/
```

## Modulos del sistema

### Login

Archivo principal:

```text
login.php
```

Permite autenticar usuarios mediante correo y contrasena. Si las credenciales son correctas, crea la sesion y redirige al dashboard.

### Dashboard

Archivo principal:

```text
dashboard.php
```

Muestra indicadores generales del sistema:

- Total de centros poblados.
- Total de municipalidades.
- Total de JASS.
- Total de sistemas de agua.
- Total de fichas de cloracion.

Tambien muestra listados organizados por modulos:

- Modulo I: Centros poblados.
- Modulo II: JASS.
- Modulo III: Cloracion.

### Gestion de usuarios

Archivo principal:

```text
crear_usuario.php
```

Disponible para el rol Administrador. Permite registrar usuarios nuevos, asignar roles y visualizar usuarios registrados.

### Centros poblados

Archivos principales:

```text
mantenimiento_centros.php
detalle_centro.php
cuestionario_centro.php
```

Permite registrar, buscar, editar, eliminar y consultar centros poblados. Tambien incluye un cuestionario asociado al centro poblado.

### Municipalidades

Archivo principal:

```text
mantenimiento_municipalidades.php
```

Permite administrar municipalidades y su relacion con la informacion territorial del sistema.

### JASS

Archivo principal:

```text
mantenimiento_jass.php
```

Permite registrar y administrar Juntas Administradoras de Servicios de Saneamiento vinculadas a centros poblados.

### Sistemas de agua

Archivo principal:

```text
mantenimiento_sistemas_agua.php
```

Permite registrar sistemas de agua y asociarlos a un centro poblado.

### Ficha tecnica de cloracion

Archivo principal:

```text
mantenimiento_ficha.php
```

Permite registrar informacion de control de cloro residual:

- Ubicacion.
- Sistema de abastecimiento.
- Medicion en reservorio.
- Medicion en red de distribucion.
- Observaciones.
- Responsables.
- PDF escaneado opcional.

Los archivos PDF se almacenan en:

```text
uploads/fichas/
```

### Solicitud de asistencia tecnica

Archivo principal:

```text
solicitar_asistencia.php
```

Disponible para Administrador y Municipalidad. Permite registrar solicitudes de asistencia tecnica para un centro poblado y consultar el estado de solicitudes realizadas.

### Gestion de asistencia tecnica

Archivo principal:

```text
gestionar_asistencia.php
```

Disponible para Administrador y Gobierno Regional. Permite revisar solicitudes, aceptarlas y programarlas con fecha, hora, tecnico asignado, zona y observaciones.

## Flujo de trabajo recomendado

1. Iniciar sesion como Administrador.
2. Registrar usuarios segun el rol que corresponda.
3. Registrar centros poblados.
4. Registrar municipalidades, JASS y sistemas de agua.
5. Completar cuestionarios y fichas tecnicas de cloracion.
6. Registrar solicitudes de asistencia tecnica desde el rol Municipalidad o Administrador.
7. Gestionar y programar asistencias desde el rol Gobierno Regional o Administrador.

## Seguridad aplicada

El proyecto incluye varias medidas basicas de seguridad:

- Sesiones PHP para controlar el acceso.
- Regeneracion del ID de sesion despues del login.
- Hash de contrasenas con `password_hash()`.
- Verificacion de contrasenas con `password_verify()`.
- Validacion de correos con `filter_var()`.
- Consultas preparadas para reducir riesgo de inyeccion SQL.
- Escape de salida HTML con `htmlspecialchars()`.
- Token CSRF en formularios de registro, edicion y gestion.
- Validacion de archivos PDF subidos.

## Solucion de problemas

### Error de conexion a la base de datos

Verifica que:

- MySQL este iniciado.
- La base de datos `sgmcr` exista.
- El usuario y contrasena de `conexion.php` coincidan con tu entorno local.
- El archivo SQL haya sido importado correctamente.

### No puedo iniciar sesion

Revisa que:

- El correo exista en la tabla `usuarios`.
- La contrasena este almacenada como hash compatible con `password_verify()`.
- El usuario tenga un rol valido en la tabla `roles`.

### No se sube el PDF de la ficha tecnica

Revisa que:

- El archivo sea PDF.
- El archivo no supere 10 MB.
- La carpeta `uploads/fichas/` exista o tenga permisos de escritura.

### Los textos aparecen con caracteres extranos

Verifica que:

- La base de datos este en `utf8mb4`.
- Las tablas usen una colacion compatible, por ejemplo `utf8mb4_unicode_ci`.
- Los archivos PHP esten guardados en UTF-8.

## Estado del proyecto

Proyecto academico en desarrollo para la gestion de informacion relacionada con agua, saneamiento rural y control de cloro residual.

## Autor

Diego Chara 
Jhon Franklin Mamani Peñasco
Soledad Maltrain Yañez

Universidad Privada de Tacna - FAING EPIS
