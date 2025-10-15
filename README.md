🏆 Torneo de Ping Pong - Anotador y Fixture
Sistema simple y autocontenido de gestión para un torneo de ping pong de eliminación directa para 8 equipos. La aplicación genera un fixture visual desde cuartos de final hasta la final, incluye un anotador en tiempo real para cada partido y guarda el progreso de forma persistente.

El acceso para modificar resultados está protegido por una contraseña de administrador, permitiendo que el fixture sea consultado por cualquier persona en modo de solo lectura.

✨ Características
Fixture para 8 Equipos: Genera y muestra un bracket completo (Cuartos, Semifinal, Final y Campeón).

Anotador en Tiempo Real: Interfaz para sumar y restar puntos durante un partido.

Persistencia de Datos: Toda la información del torneo se guarda en un archivo torneo_data.json en el servidor, sobreviviendo a cierres de navegador y reinicios.

Acceso Universal: Cualquier persona con el enlace puede ver el estado actual del torneo.

Protección por Contraseña: Solo el administrador con la clave correcta puede acceder al modo de edición de un partido para modificar los resultados.

Autocontenido: Todo el código (PHP, HTML, CSS) está en un único archivo, sin necesidad de bases de datos ni dependencias externas.

Fácil de Usar: Interfaz intuitiva para iniciar, gestionar y reiniciar el torneo.

🚀 Cómo Empezar
Sigue estos pasos para poner en funcionamiento el anotador en tu propio servidor.

Prerrequisitos
Un servidor web con soporte para PHP (por ejemplo, XAMPP, WAMP, MAMP, o cualquier hosting web estándar).

Instalación
Copia el Archivo: Coloca el archivo PHP (por ejemplo, torneo.php) en el directorio raíz de tu servidor web (como htdocs/ en XAMPP).

Permisos de Escritura:

¡Muy Importante! Asegúrate de que el servidor web tenga permisos para escribir en la carpeta donde colocaste el archivo. Esto es necesario para que PHP pueda crear y actualizar el archivo torneo_data.json que guardará el progreso del torneo.

Configura la Contraseña de Administrador:

Abre el archivo PHP con un editor de texto.

Busca la siguiente línea cerca del inicio del código:

PHP

define('ADMIN_PASSWORD', 'test2024'); // <-- CAMBIA ESTA CLAVE POR UNA PROPIA
Cambia 'test2024' por la contraseña secreta que desees utilizar.

¡Listo! Abre tu navegador y navega a la dirección donde subiste el archivo (ej. http://localhost/torneo.php).
