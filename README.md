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

4. Copie `.env.example` como `.env` y coloque el usuario y la contraseña locales de MySQL. `.env` está excluido de Git. Si el valor contiene `#`, puede dejarlo tal cual; el lector de configuración solo trata como comentarios las líneas que empiezan con `#`.
5. Abra `http://localhost/control-asistencia/instalar.php` y cree el primer administrador. La página se cierra automáticamente cuando ya existe un usuario administrativo.
6. Inicie sesión en `login.php`, registre empleados y pruebe la marcación pública en `index.php`.

Para usar el servidor de desarrollo de PHP desde la carpeta del proyecto:

```bash
php -S localhost:8000 router.php
```

Abra `http://localhost:8000/instalar.php` después de iniciar MySQL e importar el SQL.

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

## Publicar en GitHub

Si todavía no hay repositorio, inicialícelo y publique uno **público** desde su cuenta de GitHub. Antes de subirlo, compruebe que `.env` no aparezca en `git status`.

```bash
git init
git add .
git commit -m "Sistema de control de asistencia"
git branch -M main
git remote add origin https://github.com/USUARIO/control-asistencia.git
git push -u origin main
```

La creación del repositorio remoto y el `push` requieren autenticación en GitHub.
