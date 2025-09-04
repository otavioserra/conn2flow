<?php
$pdo=new PDO("mysql:host=localhost;dbname=gestor;charset=utf8mb4","root","root",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
try { $rows=$pdo->query("SELECT id_usuarios_perfis_modulos,perfil,modulo FROM usuarios_perfis_modulos ORDER BY perfil,modulo")->fetchAll(PDO::FETCH_ASSOC); }
catch(Throwable $e){ echo "Erro consulta usuarios_perfis_modulos: ".$e->getMessage()."\n"; exit(1);} 
print "usuarios_perfis_modulos (".count($rows).")\n";
foreach($rows as $r){ echo str_pad($r['perfil'],18)." | ".$r['modulo']."\n"; }
try { $rows2=$pdo->query("SELECT id_usuarios_perfis_modulos_operacoes,perfil,operacao FROM usuarios_perfis_modulos_operacoes ORDER BY perfil,operacao")->fetchAll(PDO::FETCH_ASSOC); }
catch(Throwable $e){ echo "Erro consulta usuarios_perfis_modulos_operacoes: ".$e->getMessage()."\n"; exit(1);} 
print "usuarios_perfis_modulos_operacoes (".count($rows2).")\n";
foreach($rows2 as $r){ echo str_pad($r['perfil'],18)." | ".$r['operacao']."\n"; }
