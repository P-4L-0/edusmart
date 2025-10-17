<?php
// Incluir el archivo de configuración para acceder a las configuraciones globales y proteger la página
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo administradores

// importamos las dependecias 
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;



// Crear una instancia de la base de datos
$db = new Database();

//obtenemos el grupo al que pertecen los estudiantes a exportar
$grupo_id = intval($_GET['grupo_id']);

$db->query("SELECT e.id, e.nombre_completo, e.fecha_nacimiento, 
e.grupo_id, g.nombre  AS nombre_grupo, g.grado
            FROM estudiantes e
            JOIN grupos g ON g.id = e.grupo_id
            WHERE e.grupo_id = :grupo_id
            ORDER BY e.nombre_completo");
$db->bind(':grupo_id', $grupo_id);
$estudiantes = $db->resultset(); // Devuelve un array de objetos 

//creamos el excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Escribir encabezados
$sheet->setCellValue('A1', 'NIE');
$sheet->setCellValue('B1', 'Nombre del estudiante');
$sheet->setCellValue('C1', 'Fecha de Nacimiento');
$sheet->setCellValue('D1', 'Grupo');
$sheet->setCellValue('E1', 'Grado');

//extraemos el nombre y grado del grupo
if (count($estudiantes) > 0) {
    $grupo_nombre = $estudiantes[0]->nombre_grupo;
    $grupo_grado  = $estudiantes[0]->grado;
} else {
    $grupo_nombre = "Grupo";
    $grupo_grado  = "Grado";
}

// Escribir los datos a partir de la fila 2
$rowNum = 2;
foreach ($estudiantes as $estudiante) {
    $sheet->setCellValue('A' . $rowNum, $estudiante->id); // asegurarte de que exista la columna 'nie'
    $sheet->setCellValue('B' . $rowNum, $estudiante->nombre_completo);

    // Formato de fecha dd/mm/yyyy
    $fecha = date('d/m/Y', strtotime($estudiante->fecha_nacimiento));
    $sheet->setCellValue('C' . $rowNum, $fecha);

    $sheet->setCellValue('D' . $rowNum, $estudiante->nombre_grupo);
    $sheet->setCellValue('E' . $rowNum, $estudiante->grado);

    $rowNum++;
}

// Para descargar

// Reemplazar espacios por guiones y eliminar caracteres problemáticos
$grupo_safe = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '_', $grupo_nombre));
$grado_safe = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '_', $grupo_grado));

$filename = "estudiantes_{$grupo_safe}_{$grado_safe}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit;
