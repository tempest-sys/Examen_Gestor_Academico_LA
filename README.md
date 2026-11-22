
# Gestor de Tareas Académicas

## Descripción
Aplicación web desarrollada para gestionar tareas académicas mediante operaciones CRUD.

Permite registrar, consultar, editar y eliminar tareas, además de buscar por título o materia y filtrar por estado.

## Tecnologías utilizadas
- HTML5
- CSS3
- JavaScript
- PHP 8
- MySQL
- PDO
- Visual Studio Code
- MySQL Workbench

## Funcionalidades
- Registro de tareas académicas.
- Visualización de tareas registradas.
- Edición de tareas.
- Eliminación con confirmación.
- Búsqueda por título o materia.
- Filtrado por estado.
- Validación de campos obligatorios.
- Estadísticas de tareas.

## Estructura del proyecto
- app/: configuración y conexión con MySQL.
- database/: respaldo de la base de datos.
- public/: interfaz y funcionalidades web.

## Instalación
1. Instalar PHP 8 y MySQL.
2. Descargar o clonar este repositorio.
3. Importar database/gestor_academico_la.sql en MySQL.
4. Copiar app/config.example.php como app/config.php.
5. Configurar el usuario y la contraseña de MySQL en config.php.
6. Abrir una terminal en la carpeta principal del proyecto.
7. Ejecutar:

   php -S localhost:8000 -t public

8. Abrir en el navegador:

   http://localhost:8000

## Base de datos
Nombre: gestor_academico_la

Tabla principal: tareas

Campos:
- id
- titulo
- materia
- descripcion
- fecha_entrega
- estado
- creado_en

## Asignatura
Desarrollo de Aplicaciones Web

## Proyecto
Examen práctico - Gestor de Tareas Académicas
