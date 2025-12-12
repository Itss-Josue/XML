<?php
$conexion = new mysqli("localhost", "root", "root");

$conexion->query("CREATE DATABASE IF NOT EXISTS prueba2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$conexion = new mysqli("localhost", "root", "root", "prueba2");

$conexion->query("CREATE TABLE IF NOT EXISTS sigi_programa_estudios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL
)");

$conexion->query("CREATE TABLE IF NOT EXISTS sigi_planes_estudio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_programa INT NOT NULL,
    nombre VARCHAR(20) NOT NULL,
    resolucion VARCHAR(100) NOT NULL,
    fecha_registro DATETIME NOT NULL,
    perfil_egresado TEXT NOT NULL
)");

$conexion->query("CREATE TABLE IF NOT EXISTS sigi_modulo_formativo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion TEXT NOT NULL,
    nro_modulo INT NOT NULL,
    id_plan INT NOT NULL
)");

$conexion->query("CREATE TABLE IF NOT EXISTS sigi_semestre (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(5) NOT NULL,
    id_modulo INT NOT NULL
)");

$conexion->query("CREATE TABLE IF NOT EXISTS sigi_unidad_didactica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    id_semestre INT NOT NULL,
    creditos_teorico INT NOT NULL,
    creditos_practico INT NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    horas_semanal INT,
    horas_semestral INT,
    orden INT NOT NULL
)");

echo "Conexion a la BD Correctamente.<br><br>";

$xml = simplexml_load_file('ies2.xml') or die('Error no se cargo el xml');

echo "</pre>";
echo "<hr>";
echo "<h2>IMPORTANDO DATOS A LA BASE DE DATOS...</h2>";


foreach ($xml as $i_pe => $pe) {
    echo '<h3>Programa: ' . $pe->nombre . '</h3>';
    echo 'codigo: ' . $pe->codigo . "<br>";
    echo 'tipo: ' . $pe->tipo . "<br>";
    

    $consulta = "INSERT INTO sigi_programa_estudios (codigo, tipo, nombre) 
                 VALUES ('$pe->codigo', '$pe->tipo', '$pe->nombre')";
    $conexion->query($consulta);
    $id_programa = $conexion->insert_id;
    
    foreach ($pe->planes_estudio[0] as $i_ple => $plan) {
        echo '--' . $plan->nombre . "<br>";
        echo '--' . $plan->resolucion . "<br>";
        echo '--' . $plan->fecha_registro . "<br>";
        

        $consulta = "INSERT INTO sigi_planes_estudio (id_programa, nombre, resolucion, fecha_registro, perfil_egresado) 
                     VALUES ($id_programa, '$plan->nombre', '$plan->resolucion', '$plan->fecha_registro', '')";
        $conexion->query($consulta);
        $id_plan = $conexion->insert_id;
        
        foreach ($plan->modulos_formativos[0] as $id_mod => $modulo) {
            echo '--- Módulo ' . $modulo->nro_modulo . ": " . $modulo->descripcion . "<br>";
            
            $consulta = "INSERT INTO sigi_modulo_formativo (descripcion, nro_modulo, id_plan) 
                         VALUES ('$modulo->descripcion', $modulo->nro_modulo, $id_plan)";
            $conexion->query($consulta);
            $id_modulo = $conexion->insert_id;
            
        
            foreach ($modulo->periodos[0] as $i_per => $periodo) {
                echo '-------- Semestre: ' . $periodo->descripcion . "<br>";
                
                $consulta = "INSERT INTO sigi_semestre (id_modulo, descripcion) 
                             VALUES ($id_modulo, '$periodo->descripcion')";
                $conexion->query($consulta);
                $id_semestre = $conexion->insert_id;
                
                $orden = 1;
                foreach ($periodo->unidades_didacticas[0] as $id_ud => $ud) {
                    echo '------------------ ' . $ud->nombre ."<br>";
                    echo '------------------ Créditos: ' . $ud->creditos_teorico . " teóricos, " . $ud->creditos_practico . " prácticos<br>";
                    echo '------------------ Tipo: ' . $ud->tipo . "<br>";
                    echo '------------------ Horas: ' . $ud->horas_semanal . "/semana, " . $ud->horas_semestral . "/semestre<br>";
                    
                    $consulta = "INSERT INTO sigi_unidad_didactica (id_semestre, nombre, creditos_teorico, creditos_practico, tipo, horas_semanal, horas_semestral, orden) 
                                 VALUES ($id_semestre, '$ud->nombre', $ud->creditos_teorico, $ud->creditos_practico, '$ud->tipo', $ud->horas_semanal, $ud->horas_semestral, $orden)";
                    $conexion->query($consulta);
                    
                    $orden++;
                }
            }
        }
    }
    echo "<hr>";
}

echo "<h2>¡Datos insertados Correctamente!</h2>";

// Cerrar conexión
$conexion->close();
?>