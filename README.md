# Control de asistencia

Aplicación web en PHP 8.2 y MySQL/MariaDB para administrar empleados y registrar entradas y salidas. Utiliza HTML5, Bootstrap 5, CSS, JavaScript y sesiones PHP, sin frameworks PHP.

## Requisitos

- PHP 8.2 o superior con extensiones `pdo_mysql` y `mbstring`.
- MySQL 8 o MariaDB 10.4 o superior.
- Servidor web (Apache de XAMPP o el servidor de desarrollo de PHP).
- Acceso a internet del navegador para cargar Bootstrap 5 desde CDN.

## Instalación

1. Copie el proyecto a `htdocs/control-asistencia` en XAMPP o a la carpeta servida por PHP.
2. Inicie Apache y MySQL.
3. Importe [database.sql](database.sql) en phpMyAdmin o ejecute:

   ```bash
   mysql -u root -p < database.sql
   ```

   El archivo crea una base de datos nueva llamada `control_asistencia` y sus tres tablas. Si necesita otro nombre, cámbielo tanto en el SQL como en `.env` antes de importar.

4. Cree el archivo `.env` como se explica en la sección **Configurar `.env`**. Coloque allí las credenciales de su propia instalación de MySQL.
5. Abra `http://localhost/control-asistencia/instalar.php` y cree el primer administrador. La página se cierra automáticamente cuando ya existe un usuario administrativo.
6. Inicie sesión en `login.php`, registre empleados y pruebe la marcación pública en `index.php`.

Para usar el servidor de desarrollo de PHP desde la carpeta del proyecto:

```bash
php -S localhost:8000 router.php
```

Abra `http://localhost:8000/instalar.php` después de iniciar MySQL e importar el SQL.

## Configurar `.env`

El archivo `.env` guarda la configuración local. La aplicación lo lee desde la raíz del proyecto al iniciar cada página. El repositorio incluye [.env.example](.env.example) como plantilla, pero **no publica `.env`** porque puede contener la contraseña de MySQL.

### Paso 1: crear el archivo

En Windows PowerShell, desde la carpeta del proyecto:

```powershell
Copy-Item .env.example .env
```

En macOS o Linux:

```bash
cp .env.example .env
```

También puede copiar el archivo manualmente y renombrar la copia a `.env` (con el punto al inicio, sin extensión `.txt`).

### Paso 2: completar los valores

Abra `.env` en un editor de texto. Ejemplo para una instalación local de XAMPP:

```dotenv
APP_ENV=local
APP_TIMEZONE=America/Costa_Rica
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=control_asistencia
DB_USER=root
DB_PASSWORD=TU_CONTRASENA_LOCAL
```

Reemplace `TU_CONTRASENA_LOCAL` por la contraseña real de MySQL. Si su usuario `root` no tiene contraseña, use `DB_PASSWORD=`. No copie una contraseña de ejemplo de otra persona. El nombre `DB_NAME` debe coincidir con la base creada por [database.sql](database.sql).

| Variable | Uso |
| --- | --- |
| `APP_ENV` | Identifica el entorno; use `local` para desarrollo. |
| `APP_TIMEZONE` | Zona horaria usada al registrar y mostrar las marcaciones. |
| `DB_HOST` | Servidor MySQL; `127.0.0.1` si está en el mismo equipo. |
| `DB_PORT` | Puerto MySQL; normalmente `3306`. |
| `DB_NAME` | Nombre de la base de datos importada. |
| `DB_USER` | Usuario de MySQL con acceso a esa base. |
| `DB_PASSWORD` | Contraseña de ese usuario; puede quedar vacía en instalaciones locales configuradas así. |
| `SESSION_SAVE_PATH` | Opcional: carpeta donde PHP guarda las sesiones. Si se omite, se usa la carpeta temporal del sistema. |

Escriba una variable por línea con el formato `NOMBRE=valor`. Las líneas que empiezan con `#` se ignoran. Puede rodear un valor con comillas simples o dobles; el lector elimina esas comillas externas. No agregue comentarios después del valor en la misma línea. Guarde el archivo en UTF-8.

### Paso 3: comprobar la conexión

Inicie MySQL y abra `instalar.php`. Si aparece el formulario **Crear administrador**, la aplicación pudo conectarse. Si aparece un error de conexión, revise nombre de base, servidor, puerto, usuario y contraseña; confirme además que importó `database.sql`.

Antes de publicar cambios, compruebe que `.env` no aparece en `git status`. La regla correspondiente está en [.gitignore](.gitignore). Apache y el router de desarrollo bloquean su descarga por HTTP.

## Uso

- **Marcación pública:** el empleado escribe su cédula y el código de seis caracteres asignado al registrarlo. Una entrada abierta del día se cierra con la próxima marcación; si la última ya tiene salida, se abre una entrada nueva. Se admiten varios turnos por día.
- **Empleados:** alta, consulta, edición y activación/desactivación. Cédula y código son únicos. El código se genera automáticamente y permanece igual al editar.
- **Dashboard:** muestra totales de empleados, activos, personas que ingresaron hoy, personas con una entrada abierta y registros de hoy.
- **Historial:** filtra por nombre, cédula, fecha exacta o rango; calcula horas trabajadas y exporta un CSV compatible con Excel.

Las contraseñas se almacenan con `password_hash()`. Las consultas con datos de usuario usan sentencias preparadas; los formularios POST incluyen protección CSRF y las páginas administrativas verifican la sesión.

## Archivos principales

| Archivo | Propósito |
| --- | --- |
| `database.sql` | Crea la base nueva y las tablas `usuarios`, `empleados`, `asistencias` |
| `.env.example` | Plantilla de configuración sin credenciales |
| `instalar.php`, `login.php`, `logout.php` | Alta inicial y acceso administrativo |
| `index.php` | Marcación pública |
| `dashboard.php` | Resumen administrativo |
| `empleados.php`, `empleado_form.php` | Mantenimiento de empleados |
| `asistencias.php`, `exportar_csv.php` | Historial, filtros y exportación |

## Repositorio

Código público: <https://github.com/Fabian2812/control-asistencia>. Al clonar el proyecto, cree su propio `.env` siguiendo la sección anterior; ese archivo nunca debe incluirse en un commit.
