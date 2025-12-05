<?php
$conexion = new mysqli("localhost", "root", "root", "para_xml");
if ($conexion->connect_errno) {
    echo "fallo la conexion  (" . $conexion->connect_errno . ")" . $conexion->connect_error;
}
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$et1 = $xml->createElement('programas_estudio');
$xml->appendChild($et1);

$semanas_semestre = 16;

$consulta = "SELECT * FROM sigi_programa_estudios";
$resultado = $conexion->query($consulta);
while ($pe = mysqli_fetch_assoc($resultado)) {
    echo $pe['nombre'] . "<br>";
    $num_pe = $xml->createElement('pe_' . $pe['id']);
    $codigo_pe = $xml->createElement('codigo', $pe['codigo']);
    $num_pe->appendChild($codigo_pe);
    $tipo_pe = $xml->createElement('tipo', $pe['tipo']);
    $num_pe->appendChild($tipo_pe);
    $nombre_pe = $xml->createElement('nombre', $pe['nombre']);
    $num_pe->appendChild($nombre_pe);

    $et_plan = $xml->createElement('planes_estudio');
    $consulta_plan = "SELECT * FROM sigi_planes_estudio WHERE id_programa_estudios = " . (int)$pe['id'];
    $resultado_plan = $conexion->query($consulta_plan);
    while ($plan = mysqli_fetch_assoc($resultado_plan)) {
        echo $plan['nombre'] . "<br>";
        $num_plan = $xml->createElement('plan_' . $plan['id']);
        $nombre_plan = $xml->createElement('nombre', $plan['nombre']);
        $num_plan->appendChild($nombre_plan);
        $resol_plan = $xml->createElement('resolucion', $plan['resolucion']);
        $num_plan->appendChild($resol_plan);
        $fecha_plan = $xml->createElement('fecha_registro', $plan['fecha_registro']);
        $num_plan->appendChild($fecha_plan);


        $consulta_mod = "SELECT * FROM sigi_modulo_formativo WHERE id_plan_estudio = " . (int)$plan['id'];
        $res_mod = $conexion->query($consulta_mod);
        while ($m = mysqli_fetch_assoc($res_mod)) {
            echo $m['descripcion'] . "<br>";
            $num_mod = $xml->createElement('mod_' . $m['id']);
            $nro_mod = $xml->createElement('nro_modulo', $m['nro_modulo']);
            $num_mod->appendChild($nro_mod);
            $desc_mod = $xml->createElement('descripcion', $m['descripcion']);
            $num_mod->appendChild($desc_mod);


            $consulta_sem = "SELECT * FROM sigi_semestre WHERE id_modulo_formativo = " . (int)$m['id'];
            $res_sem = $conexion->query($consulta_sem);
            while ($s = mysqli_fetch_assoc($res_sem)) {
                echo $s['descripcion'] . "<br>";
                $num_sem = $xml->createElement('sem_' . $s['id']);
                $desc_sem = $xml->createElement('descripcion', $s['descripcion']);
                $num_sem->appendChild($desc_sem);

                 $consulta_ud = "SELECT * FROM sigi_unidad_didactica WHERE id_semestre = " . (int)$s['id'];
                $res_ud = $conexion->query($consulta_ud);
                while ($ud = mysqli_fetch_assoc($res_ud)) {
                    echo $ud['nombre'] . "<br>";
                    $num_ud = $xml->createElement('ud_' . $ud['id']);
                    $nombre_ud = $xml->createElement('nombre', $ud['nombre']);
                    $num_ud->appendChild($nombre_ud);
                    $ct_ud = $xml->createElement('creditos_teorico', $ud['creditos_teorico']);
                    $num_ud->appendChild($ct_ud);
                    $cp_ud = $xml->createElement('creditos_practico', $ud['creditos_practico']);
                    $num_ud->appendChild($cp_ud);
                    $tipo_ud = $xml->createElement('tipo', $ud['tipo']);
                    $num_ud->appendChild($tipo_ud);
                    $orden_ud = $xml->createElement('orden', $ud['orden']);
                    $num_ud->appendChild($orden_ud);


                    $horas_semanales = ((int)$ud['creditos_teorico'] * 1) + ((int)$ud['creditos_practico'] * 2);
                    $horas_semanales_node = $xml->createElement('horas_semanales', $horas_semanales);
                    $num_ud->appendChild($horas_semanales_node);

                    $horas_semestrales = $horas_semanales * $semanas_semestre;
                    $horas_semestrales_node = $xml->createElement('horas_semestrales', $horas_semestrales);
                    $num_ud->appendChild($horas_semestrales_node);

                    $num_sem->appendChild($num_ud);
                }

                $num_mod->appendChild($num_sem);
            }

            $num_plan->appendChild($num_mod);
        }

        $et_plan->appendChild($num_plan);
    }
    $num_pe->appendChild($et_plan);
    $et1->appendChild($num_pe);
}

$archivo = "ies2.xml";
$xml->save($archivo);

echo "XML generado correctamente: $archivo";
?>
