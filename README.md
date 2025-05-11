# SmartEdu

SmartEdu es una plataforma de gestión educativa diseñada para facilitar la administración de usuarios, grupos, materias y actividades.

## Requisitos

- **Servidor**: Apache con soporte para PHP.
- **PHP**: Versión 8.0 o superior.
- **Base de datos**: MySQL.
- **Dependencias**:
  - [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) para la gestión de hojas de cálculo.
  - [TCPDF](https://github.com/tecnickcom/TCPDF) para la generación de PDFs.

## Instalación

1. Clona este repositorio en tu servidor local:
   ```sh
   git clone https://github.com/tu-usuario/smartedu.git
2. Instala las dependencias con Composer:
    composer install
3. Configura la base de datos en el archivo includes/config.php.
4. Importa el archivo SQL en tu base de datos:
    mysql -u usuario -p base_de_datos < sql/estructura.sql
5. Asegúrate de que las carpetas necesarias tengan permisos de escritura si es requerido.

Licencia
Este proyecto está licenciado bajo la MIT License.

Créditos
    -PhpSpreadsheet: Para la gestión de hojas de cálculo.
    -TCPDF: Para la generación de PDFs.
