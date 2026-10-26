<?php
require_once "../../conexion.php";require_once "../../includes/auth.php";require_once "../../includes/equipos_red.php";exigir_rol('admin');
$eq=(int)($_POST['equipo_id']??0);$ingreso=date('Y-m-d');$problema=trim($_POST['problema']??'');$diag=trim($_POST['diagnostico']??'');$sol=trim($_POST['solucion']??'');$estado=$_POST['estado']??'Ingresado';$obs=trim($_POST['observaciones']??'');
$permitidas=['Ingresado','Diagnóstico','En reparación','Reparado','Entregado','Reparado y entregado','No reparable'];if(!in_array($estado,$permitidas,true))$estado='Ingresado';
$stmt=$conexion->prepare("INSERT INTO reparaciones(equipo_id,problema,diagnostico,solucion,fecha_ingreso,estado,observaciones) VALUES(?,?,?,?,?,?,?)");$stmt->bind_param("issssss",$eq,$problema,$diag,$sol,$ingreso,$estado,$obs);$stmt->execute();
if($estado==='No reparable'){$s=$conexion->prepare("UPDATE equipos SET estado='Baja' WHERE id=?");$s->bind_param("i",$eq);$s->execute();registrar_historial_equipo($conexion,$eq,'Reparación','Reparación registrada (No reparable): '.($problema!==''?$problema:'sin descripción').'. Estado del equipo: "Baja".');}
elseif(!in_array($estado,['Entregado','Reparado y entregado'],true)){$s=$conexion->prepare("UPDATE equipos SET estado='En reparación' WHERE id=?");$s->bind_param("i",$eq);$s->execute();registrar_historial_equipo($conexion,$eq,'Reparación','Reparación registrada ('.$estado.'): '.($problema!==''?$problema:'sin descripción').'. Estado del equipo: "En reparación".');}
header("Location: reparaciones.php?ok=Reparación registrada.");exit;
?>
