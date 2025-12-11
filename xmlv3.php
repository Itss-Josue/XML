<?php
$conexion = new mysqli("localhost", "root", "root");
if ($conexion->connect_errno) {
    echo "Fallo al conectar a MySQL: (" . $conexion->connect_errno . ")" . $conexion->connect_error;
    exit();
}

// Crear base de datos si no existe (exactamente como en tu archivo SQL)
$sql = "CREATE DATABASE IF NOT EXISTS sigi_programas_estudios CHARACTER SET utf8 COLLATE utf8_spanish2_ci";
if ($conexion->query($sql) === FALSE) {
    echo "Error al crear la base de datos: " . $conexion->error;
}

// Seleccionar la base de datos
$conexion->select_db("sigi_programas_estudios");

// Crear tabla sigi_programa_estudios (exactamente como en tu archivo SQL)
$sql = "CREATE TABLE IF NOT EXISTS sigi_programa_estudios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci";

if ($conexion->query($sql) === FALSE) {
    echo "Error al crear tabla sigi_programa_estudios: " . $conexion->error;
}

// Crear tabla sigi_planes_estudio (exactamente como en tu archivo SQL)
$sql = "CREATE TABLE IF NOT EXISTS sigi_planes_estudio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_programa_estudios INT NOT NULL,
    nombre VARCHAR(20) NOT NULL,
    resolucion VARCHAR(100) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    perfil_egresado VARCHAR(3000) NOT NULL DEFAULT '',
    FOREIGN KEY (id_programa_estudios) REFERENCES sigi_programa_estudios(id) ON DELETE CASCADE,
    INDEX idx_id_programa_estudios (id_programa_estudios)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci";

if ($conexion->query($sql) === FALSE) {
    echo "Error al crear tabla sigi_planes_estudio: " . $conexion->error;
}

// Crear tabla sigi_modulo_formativo (exactamente como en tu archivo SQL)
$sql = "CREATE TABLE IF NOT EXISTS sigi_modulo_formativo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(1000) NOT NULL,
    nro_modulo INT NOT NULL,
    id_plan_estudio INT NOT NULL,
    FOREIGN KEY (id_plan_estudio) REFERENCES sigi_planes_estudio(id) ON DELETE CASCADE,
    INDEX idx_id_plan_estudio (id_plan_estudio),
    INDEX idx_nro_modulo (nro_modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci";

if ($conexion->query($sql) === FALSE) {
    echo "Error al crear tabla sigi_modulo_formativo: " . $conexion->error;
}

// Crear tabla sigi_semestre (exactamente como en tu archivo SQL)
$sql = "CREATE TABLE IF NOT EXISTS sigi_semestre (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(5) NOT NULL,
    id_modulo_formativo INT NOT NULL,
    FOREIGN KEY (id_modulo_formativo) REFERENCES sigi_modulo_formativo(id) ON DELETE CASCADE,
    INDEX idx_id_modulo_formativo (id_modulo_formativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci";

if ($conexion->query($sql) === FALSE) {
    echo "Error al crear tabla sigi_semestre: " . $conexion->error;
}

// Crear tabla sigi_unidad_didactica (exactamente como en tu archivo SQL + campos adicionales del XML)
$sql = "CREATE TABLE IF NOT EXISTS sigi_unidad_didactica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    id_semestre INT NOT NULL,
    creditos_teorico INT NOT NULL,
    creditos_practico INT NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    horas_semanal INT NOT NULL DEFAULT 0,
    horas_semestral INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_semestre) REFERENCES sigi_semestre(id) ON DELETE CASCADE,
    INDEX idx_id_semestre (id_semestre),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci";

if ($conexion->query($sql) === FALSE) {
    echo "Error al crear tabla sigi_unidad_didactica: " . $conexion->error;
}

echo "Base de datos y tablas creadas exitosamente.<br><br>";

// Cargar el archivo XML
$xml = simplexml_load_file('ies2.xml');
if (!$xml) {
    die('Error: No se pudo cargar el archivo XML.');
}

// Insertar datos del XML en las tablas - Manteniendo EXACTAMENTE la misma estructura de anidamiento
foreach ($xml as $i_pe => $pe) {
    // Mostrar datos del programa
    echo 'codigo: ' . $pe->codigo . "<br>";
    echo 'tipo: ' . $pe->tipo . "<br>";
    echo 'nombre: ' . $pe->nombre . "<br>";
    
    // Insertar en sigi_programa_estudios
    $codigo = $conexion->real_escape_string((string)$pe->codigo);
    $tipo = $conexion->real_escape_string((string)$pe->tipo);
    $nombre = $conexion->real_escape_string((string)$pe->nombre);
    
    // Verificar si el programa ya existe
    $check_sql = "SELECT id FROM sigi_programa_estudios WHERE codigo = '$codigo'";
    $result = $conexion->query($check_sql);
    
    if ($result->num_rows == 0) {
        $sql = "INSERT INTO sigi_programa_estudios (codigo, tipo, nombre) 
                VALUES ('$codigo', '$tipo', '$nombre')";
        
        if ($conexion->query($sql) === TRUE) {
            $id_programa_estudios = $conexion->insert_id;
            echo "Programa insertado en BD (ID: $id_programa_estudios)<br>";
        } else {
            echo "Error al insertar programa: " . $conexion->error . "<br>";
            continue;
        }
    } else {
        $row = $result->fetch_assoc();
        $id_programa_estudios = $row['id'];
        echo "Programa ya existe en BD (ID: $id_programa_estudios)<br>";
    }
    
    foreach ($pe->planes_estudio[0] as $i_ple => $plan) {
        // Mostrar datos del plan
        echo '--' . $plan->nombre . "<br>";
        echo '--' . $plan->resolucion . "<br>";
        echo '--' . $plan->fecha_registro . "<br>";
        
        // Insertar en sigi_planes_estudio
        $nombre_plan = $conexion->real_escape_string((string)$plan->nombre);
        $resolucion = $conexion->real_escape_string((string)$plan->resolucion);
        $fecha_registro = $conexion->real_escape_string((string)$plan->fecha_registro);
        
        // Verificar si el plan ya existe
        $check_sql = "SELECT id FROM sigi_planes_estudio 
                      WHERE id_programa_estudios = $id_programa_estudios 
                      AND nombre = '$nombre_plan' 
                      AND fecha_registro = '$fecha_registro'";
        $result = $conexion->query($check_sql);
        
        if ($result->num_rows == 0) {
            $sql = "INSERT INTO sigi_planes_estudio (id_programa_estudios, nombre, resolucion, fecha_registro) 
                    VALUES ($id_programa_estudios, '$nombre_plan', '$resolucion', '$fecha_registro')";
            
            if ($conexion->query($sql) === TRUE) {
                $id_plan_estudio = $conexion->insert_id;
                echo "--Plan insertado en BD (ID: $id_plan_estudio)<br>";
            } else {
                echo "--Error al insertar plan: " . $conexion->error . "<br>";
                continue;
            }
        } else {
            $row = $result->fetch_assoc();
            $id_plan_estudio = $row['id'];
            echo "--Plan ya existe en BD (ID: $id_plan_estudio)<br>";
        }
        
        foreach ($plan->modulos_formativos[0] as $id_mod => $modulo) {
            // Mostrar datos del módulo
            echo '----' . $modulo->nro_modulo . "<br>";
            echo '----' . $modulo->descripcion . "<br>";
            
            // Insertar en sigi_modulo_formativo
            $nro_modulo = (int)$modulo->nro_modulo;
            $descripcion = $conexion->real_escape_string((string)$modulo->descripcion);
            
            // Verificar si el módulo ya existe
            $check_sql = "SELECT id FROM sigi_modulo_formativo 
                          WHERE id_plan_estudio = $id_plan_estudio 
                          AND nro_modulo = $nro_modulo 
                          AND descripcion = '$descripcion'";
            $result = $conexion->query($check_sql);
            
            if ($result->num_rows == 0) {
                $sql = "INSERT INTO sigi_modulo_formativo (descripcion, nro_modulo, id_plan_estudio) 
                        VALUES ('$descripcion', $nro_modulo, $id_plan_estudio)";
                
                if ($conexion->query($sql) === TRUE) {
                    $id_modulo_formativo = $conexion->insert_id;
                    echo "----Módulo insertado en BD (ID: $id_modulo_formativo)<br>";
                } else {
                    echo "----Error al insertar módulo: " . $conexion->error . "<br>";
                    continue;
                }
            } else {
                $row = $result->fetch_assoc();
                $id_modulo_formativo = $row['id'];
                echo "----Módulo ya existe en BD (ID: $id_modulo_formativo)<br>";
            }
            
            foreach ($modulo->periodos[0] as $i_per => $periodo) {
                // Mostrar datos del período (semestre)
                echo '------' . $periodo->descripcion . "<br>";
                
                // Insertar en sigi_semestre
                $desc_periodo = $conexion->real_escape_string((string)$periodo->descripcion);
                
                // Verificar si el semestre ya existe
                $check_sql = "SELECT id FROM sigi_semestre 
                              WHERE id_modulo_formativo = $id_modulo_formativo 
                              AND descripcion = '$desc_periodo'";
                $result = $conexion->query($check_sql);
                
                if ($result->num_rows == 0) {
                    $sql = "INSERT INTO sigi_semestre (descripcion, id_modulo_formativo) 
                            VALUES ('$desc_periodo', $id_modulo_formativo)";
                    
                    if ($conexion->query($sql) === TRUE) {
                        $id_semestre = $conexion->insert_id;
                        echo "------Semestre insertado en BD (ID: $id_semestre)<br>";
                    } else {
                        echo "------Error al insertar semestre: " . $conexion->error . "<br>";
                        continue;
                    }
                } else {
                    $row = $result->fetch_assoc();
                    $id_semestre = $row['id'];
                    echo "------Semestre ya existe en BD (ID: $id_semestre)<br>";
                }
                
                $orden_ud = 1; // Contador para el campo 'orden'
                foreach ($periodo->unidades_didacticas[0] as $id_ud => $ud) {
                    // Mostrar datos de la unidad didáctica
                    echo '--------' . $ud->nombre . "<br>";
                    echo '--------' . $ud->creditos_teorico . "<br>";
                    echo '--------' . $ud->creditos_practico . "<br>";
                    echo '--------' . $ud->tipo . "<br>";
                    echo '--------' . $ud->horas_semanal . "<br>";
                    echo '--------' . $ud->horas_semestral . "<br>";
                    
                    // Insertar en sigi_unidad_didactica
                    $nombre_ud = $conexion->real_escape_string((string)$ud->nombre);
                    $creditos_teorico = (int)$ud->creditos_teorico;
                    $creditos_practico = (int)$ud->creditos_practico;
                    $tipo = $conexion->real_escape_string((string)$ud->tipo);
                    $horas_semanal = (int)$ud->horas_semanal;
                    $horas_semestral = (int)$ud->horas_semestral;
                    
                    // Verificar si la unidad ya existe
                    $check_sql = "SELECT id FROM sigi_unidad_didactica 
                                  WHERE id_semestre = $id_semestre 
                                  AND nombre = '$nombre_ud' 
                                  AND tipo = '$tipo'";
                    $result = $conexion->query($check_sql);
                    
                    if ($result->num_rows == 0) {
                        $sql = "INSERT INTO sigi_unidad_didactica 
                                (nombre, id_semestre, creditos_teorico, creditos_practico, 
                                 tipo, orden, horas_semanal, horas_semestral) 
                                VALUES ('$nombre_ud', $id_semestre, $creditos_teorico, 
                                        $creditos_practico, '$tipo', $orden_ud, 
                                        $horas_semanal, $horas_semestral)";
                        
                        if ($conexion->query($sql) === TRUE) {
                            $id_unidad_didactica = $conexion->insert_id;
                            echo "--------Unidad insertada en BD (ID: $id_unidad_didactica, Orden: $orden_ud)<br>";
                        } else {
                            echo "--------Error al insertar unidad: " . $conexion->error . "<br>";
                        }
                    } else {
                        echo "--------Unidad ya existe en BD (Orden: $orden_ud)<br>";
                    }
                    
                    $orden_ud++; // Incrementar el orden para la siguiente unidad
                }
            }
        }
    }
    echo "<br>";
}

echo "<br>¡Proceso completado! Todos los datos han sido importados a la base de datos 'sigi_programas_estudios'.";

$conexion->close();
?>