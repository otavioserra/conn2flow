<?php
/**
 * Geração de múltiplos Data.json para Plugin (Layouts, Páginas, Componentes, Variáveis)
 * ----------------------------------------------------------------------------
 * Replica a lógica do script core `atualizacao-dados-recursos.php` porém usando
 * como origem a estrutura do PLUGIN alvo (resources + modules) ao invés do gestor.
 *
 * Saída:
 *  - db/data/LayoutsData.json
 *  - db/data/PaginasData.json
 *  - db/data/ComponentesData.json
 *  - db/data/VariaveisData.json
 *  - db/orphans/<Tipo>Data.json (recursos descartados por duplicidade/regras)
 *
 * Diferenças vs core: não atualiza arquivos de origem (layouts.json etc) ainda.
 */
declare(strict_types=1);

$pluginRoot = __DIR__ . '/plugin';
if (!is_dir($pluginRoot)) {
  fwrite(STDERR, "Plugin root não encontrado: $pluginRoot\n");
  exit(1);
}
$resourcesDir = $pluginRoot . '/resources';
$modulesDir   = $pluginRoot . '/modules';
$dataDir      = $pluginRoot . '/db/data';
$orphDir      = $pluginRoot . '/db/orphans';
@mkdir($dataDir,0775,true); @mkdir($orphDir,0775,true);

// ================= Utilidades =================
function jsonRead(string $p): ?array { if(!is_file($p)) return null; $d=json_decode(file_get_contents($p),true); return is_array($d)?$d:null; }
function jsonWrite(string $p, array $data): void { @mkdir(dirname($p),0775,true); file_put_contents($p,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); }
function readFileIfExists(string $p): ?string { return is_file($p)?file_get_contents($p):null; }
function buildChecksum(?string $html, ?string $css): array { // md5 para compatibilidade com core
  $h = ($html===null||$html==='')? '': md5($html);
  $c = ($css===null||$css==='')? '': md5($css);
  $combined = ($h==='' && $c==='')? '': md5(($html??'').($css??''));
  return ['html'=>$h,'css'=>$c,'combined'=>$combined];
}
function checksumsEqual(array $a, array $b): bool { return ($a['html']??null)==($b['html']??null) && ($a['css']??null)==($b['css']??null) && ($a['combined']??null)==($b['combined']??null); }
function incrementVersion(?string $v): string { if(!$v) return '1.0'; $p=explode('.',$v); if(count($p)==2 && ctype_digit($p[1])) { $p[1]=(string)((int)$p[1]+1); return implode('.',$p);} return '1.0'; }
function resourcePaths(string $base, string $lang, string $type, string $id, bool $baseIsResourcesDir=false): array {
  $baseDir = $baseIsResourcesDir? $base : $base.'/resources';
  $dir = $baseDir.'/'.$lang.'/'.$type.'/'.$id;
  return ['html'=>$dir.'/'.$id.'.html','css'=>$dir.'/'.$id.'.css'];
}

// ============ Carregar dados existentes (para manter versao) ============
$existing = [
  'layouts'=>[], 'paginas'=>[], 'componentes'=>[], 'variaveis'=>[]
];
foreach ([ 'layouts'=> 'LayoutsData.json', 'paginas'=>'PaginasData.json', 'componentes'=>'ComponentesData.json', 'variaveis'=>'VariaveisData.json'] as $tipo=>$file) {
  $path = $dataDir.'/'.$file; $arr = jsonRead($path) ?? [];
  foreach ($arr as $r) {
    switch($tipo){
      case 'layouts':
      case 'componentes':
        if(isset($r['language'],$r['id'])) $existing[$tipo][$r['language'].'|'.$r['id']] = $r; break;
      case 'paginas':
        if(isset($r['language'],$r['id'])) $existing[$tipo][$r['language'].'|'.($r['modulo']??'').'|'.$r['id']] = $r; break;
      case 'variaveis':
        if(isset($r['linguagem_codigo'],$r['id'])) $existing[$tipo][$r['linguagem_codigo'].'|'.($r['modulo']??'').'|'.$r['id'].'|'.($r['grupo']??'')] = $r; break;
    }
  }
}

// ============ Carregar mapeamento de idiomas ============
$mapFile = $resourcesDir.'/resources.map.php';
if(!is_file($mapFile)) { fwrite(STDERR,"resources.map.php não encontrado no plugin.\n"); exit(1);} 
$map = include $mapFile; if(!isset($map['languages'])) { fwrite(STDERR,"Estrutura inválida em resources.map.php.\n"); exit(1);} 
$languages = array_keys($map['languages']);

// ============ Estruturas de Resultado ============
$layoutsData = $pagesData = $componentsData = $variablesData = [];
$orphans = [ 'layouts'=>[], 'paginas'=>[], 'componentes'=>[], 'variaveis'=>[] ];

// Índices de unicidade (mesmas regras v2 simplificadas)
$idxLayouts = $idxComponentes = [];
$idxPaginasId = $idxPaginasPath = [];
$idxVariaveis = [];

// Helper versão + checksum
$versaoChecksum = function(string $tipo, string $key, ?string $html, ?string $css) use (&$existing): array {
  $cks = buildChecksum($html,$css);
  $versao = 1; $oldVersao = null;
  if(isset($existing[$tipo][$key])) {
    $old = $existing[$tipo][$key];
    $oldChecksum = $old['checksum'] ?? null;
    if(is_string($oldChecksum)) { $dec=json_decode($oldChecksum,true); if(is_array($dec)) $oldChecksum=$dec; }
    if(is_array($oldChecksum) && checksumsEqual($oldChecksum,$cks)) {
      $versao = (int)($old['versao']??1);
    } else {
      $versao = (int)($old['versao']??1)+1;
    }
    $oldVersao = $old['versao'] ?? null;
  }
  return [$versao,$cks,$oldVersao];
};

// ============ Globais ============
foreach ($languages as $lang) {
  $langInfo = $map['languages'][$lang] ?? null; if(!$langInfo || !isset($langInfo['data'])) continue;
  $dataFiles = $langInfo['data'];
  // Layouts
  if(!empty($dataFiles['layouts'])) {
    $file = $resourcesDir.'/'.$lang.'/'.$dataFiles['layouts']; $lista = jsonRead($file) ?? [];
    foreach ($lista as $l) {
      $id = $l['id'] ?? null; if(!$id){ $orphans['layouts'][]=$l+['_motivo'=>'sem id','language'=>$lang]; continue; }
      $key = $lang.'|'.$id; if(isset($idxLayouts[$key])) { $orphans['layouts'][]=$l+['_motivo'=>'duplicidade id','language'=>$lang]; continue; }
      $idxLayouts[$key]=true;
      $paths = resourcePaths($resourcesDir,$lang,'layouts',$id,true); $html = readFileIfExists($paths['html']); $css=readFileIfExists($paths['css']);
      [$versao,$cks] = $versaoChecksum('layouts',$key,$html,$css);
      $layoutsData[] = [
        'nome'=>$l['name'] ?? ($l['nome'] ?? $id),
        'id'=>$id,
        'language'=>$lang,
        'html'=>$html,'css'=>$css,
        'framework_css'=>$l['framework_css'] ?? ($l['framework_css'] ?? null),
        'status'=>$l['status'] ?? 'A',
        'versao'=>$versao,
        'file_version'=>$l['version'] ?? null,
        'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE)
      ];
    }
  }
  // Componentes
  if(!empty($dataFiles['components'])) {
    $file = $resourcesDir.'/'.$lang.'/'.$dataFiles['components']; $lista = jsonRead($file) ?? [];
    foreach ($lista as $c) {
      $id = $c['id'] ?? null; if(!$id){ $orphans['componentes'][]=$c+['_motivo'=>'sem id','language'=>$lang]; continue; }
      $key = $lang.'|'.$id; if(isset($idxComponentes[$key])) { $orphans['componentes'][]=$c+['_motivo'=>'duplicidade id','language'=>$lang]; continue; }
      $idxComponentes[$key]=true; $paths = resourcePaths($resourcesDir,$lang,'components',$id,true); $html=readFileIfExists($paths['html']); $css=readFileIfExists($paths['css']);
      [$versao,$cks] = $versaoChecksum('componentes',$key,$html,$css);
      $componentsData[] = [
        'nome'=>$c['name'] ?? ($c['nome'] ?? $id),
        'id'=>$id,
        'language'=>$lang,
        'modulo'=>$c['module'] ?? ($c['modulo'] ?? null),
        'html'=>$html,'css'=>$css,
        'framework_css'=>$c['framework_css'] ?? null,
        'status'=>$c['status'] ?? 'A',
        'versao'=>$versao,
        'file_version'=>$c['version'] ?? null,
        'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE)
      ];
    }
  }
  // Páginas
  if(!empty($dataFiles['pages'])) {
    $file = $resourcesDir.'/'.$lang.'/'.$dataFiles['pages']; $lista = jsonRead($file) ?? [];
    foreach ($lista as $p) {
      $id = $p['id'] ?? null; if(!$id){ $orphans['paginas'][]=$p+['_motivo'=>'sem id','language'=>$lang]; continue; }
      $mod = $p['module'] ?? ($p['modulo'] ?? null);
      $path = $p['path'] ?? ($p['caminho'] ?? ($id.'/'));
      $kId = $lang.'|'.($mod??'').'|'.$id; if(isset($idxPaginasId[$kId])) { $orphans['paginas'][]=$p+['_motivo'=>'duplicidade id','language'=>$lang]; continue; }
      $kPath = $lang.'|'.strtolower(trim($path,'/')); if(isset($idxPaginasPath[$kPath])) { $orphans['paginas'][]=$p+['_motivo'=>'duplicidade caminho','language'=>$lang]; continue; }
      $idxPaginasId[$kId]=true; $idxPaginasPath[$kPath]=true;
      $paths = resourcePaths($resourcesDir,$lang,'pages',$id,true); $html=readFileIfExists($paths['html']); $css=readFileIfExists($paths['css']);
      [$versao,$cks] = $versaoChecksum('paginas',$kId,$html,$css);
      $pagesData[] = [
        'layout_id'=>$p['layout'] ?? null,
        'nome'=>$p['name'] ?? ($p['nome'] ?? $id),
        'id'=>$id,
        'language'=>$lang,
        'caminho'=>$path,
        'tipo'=>$p['type'] ?? ($p['tipo'] ?? null),
        'modulo'=>$mod,
        'opcao'=>$p['option'] ?? ($p['opcao'] ?? null),
        'raiz'=>$p['root'] ?? ($p['raiz'] ?? null),
        'sem_permissao'=>$p['without_permission'] ?? ($p['sem_permissao'] ?? null),
        'html'=>$html,'css'=>$css,
        'framework_css'=>$p['framework_css'] ?? null,
        'status'=>$p['status'] ?? 'A',
        'versao'=>$versao,
        'file_version'=>$p['version'] ?? null,
        'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE)
      ];
    }
  }
  // Variáveis globais
  if(!empty($dataFiles['variables'])) {
    $file = $resourcesDir.'/'.$lang.'/'.$dataFiles['variables']; $lista = jsonRead($file) ?? [];
    foreach ($lista as $v) {
      $id = $v['id'] ?? null; if(!$id){ $orphans['variaveis'][]=$v+['_motivo'=>'sem id','linguagem_codigo'=>$lang]; continue; }
      $mod = $v['module'] ?? ($v['modulo'] ?? ''); $grp = $v['group'] ?? ($v['grupo'] ?? null);
      $base = $lang.'|'.$mod.'|'.$id; if(!isset($idxVariaveis[$base])) $idxVariaveis[$base]=[];
      $groups = $idxVariaveis[$base];
      if($grp===null || $grp==='') { if(!empty($groups) || in_array('', $groups,true)){ $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade sem group','linguagem_codigo'=>$lang]; continue; } }
      else { if(in_array($grp,$groups,true)){ $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade group repetido','linguagem_codigo'=>$lang]; continue; } }
      $idxVariaveis[$base][] = ($grp ?? '');
      $variablesData[] = [
        'linguagem_codigo'=>$lang,
        'modulo'=>$mod!==''?$mod:null,
        'id'=>$id,
        'valor'=>$v['value'] ?? ($v['valor'] ?? null),
        'tipo'=>$v['type'] ?? ($v['tipo'] ?? null),
        'grupo'=>$grp,
        'descricao'=>$v['description'] ?? ($v['descricao'] ?? null)
      ];
    }
  }
}

// ============ Módulos ============
if(is_dir($modulesDir)) {
  $mods = glob($modulesDir.'/*', GLOB_ONLYDIR) ?: [];
  foreach($mods as $modPath) {
    $modId = basename($modPath);
    $jsonFile = $modPath.'/'.$modId.'.json'; $data = jsonRead($jsonFile); if(!$data || empty($data['resources'])) continue;
    foreach($languages as $lang) {
      if(empty($data['resources'][$lang])) continue; $res = $data['resources'][$lang];
      foreach(['layouts','components','pages'] as $tipo) {
        $arr = $res[$tipo] ?? []; foreach($arr as $item) {
          $id = $item['id'] ?? null; if(!$id) continue; $paths = resourcePaths($modPath,$lang,$tipo,$id); $html=readFileIfExists($paths['html']); $css=readFileIfExists($paths['css']);
          if($tipo==='layouts') {
            $key=$lang.'|'.$id; if(isset($idxLayouts[$key])) { $orphans['layouts'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
            $idxLayouts[$key]=true; [$versao,$cks]=$versaoChecksum('layouts',$key,$html,$css);
            $layoutsData[] = [ 'nome'=>$item['name'] ?? $id, 'id'=>$id,'language'=>$lang,'modulo'=>$modId,'html'=>$html,'css'=>$css,'framework_css'=>$item['framework_css'] ?? null,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
          } elseif($tipo==='components') {
            $key=$lang.'|'.$id; if(isset($idxComponentes[$key])) { $orphans['componentes'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
            $idxComponentes[$key]=true; [$versao,$cks]=$versaoChecksum('componentes',$key,$html,$css);
            $componentsData[] = [ 'nome'=>$item['name'] ?? $id, 'id'=>$id,'language'=>$lang,'modulo'=>$modId,'html'=>$html,'css'=>$css,'framework_css'=>$item['framework_css'] ?? null,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
          } else { // pages
            $path = $item['path'] ?? ($id.'/'); $kId=$lang.'|'.$modId.'|'.$id; if(isset($idxPaginasId[$kId])) { $orphans['paginas'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
            $kPath=$lang.'|'.strtolower(trim($path,'/')); if(isset($idxPaginasPath[$kPath])) { $orphans['paginas'][]=$item+['_motivo'=>'duplicidade caminho','language'=>$lang,'modulo'=>$modId]; continue; }
            $idxPaginasId[$kId]=true; $idxPaginasPath[$kPath]=true; [$versao,$cks]=$versaoChecksum('paginas',$kId,$html,$css);
            $pagesData[] = [ 'layout_id'=>$item['layout'] ?? null,'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'caminho'=>$path,'tipo'=>$item['type'] ?? null,'modulo'=>$modId,'opcao'=>$item['option'] ?? null,'raiz'=>$item['root'] ?? null,'sem_permissao'=>$item['without_permission'] ?? null,'html'=>$html,'css'=>$css,'framework_css'=>$item['framework_css'] ?? null,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
          }
        } }
      if(!empty($res['variables'])) {
        foreach($res['variables'] as $v) {
          $id=$v['id']??null; if(!$id) continue; $grp=$v['group']??null; $base=$lang.'|'.$modId.'|'.$id; if(!isset($idxVariaveis[$base])) $idxVariaveis[$base]=[]; $groups=$idxVariaveis[$base];
          if($grp===null || $grp==='') { if(!empty($groups) || in_array('', $groups,true)) { $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade sem group','linguagem_codigo'=>$lang,'modulo'=>$modId]; continue; } }
          else { if(in_array($grp,$groups,true)) { $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade group repetido','linguagem_codigo'=>$lang,'modulo'=>$modId]; continue; } }
          $idxVariaveis[$base][] = ($grp ?? '');
          $variablesData[] = [ 'linguagem_codigo'=>$lang,'modulo'=>$modId,'id'=>$id,'valor'=>$v['value'] ?? null,'tipo'=>$v['type'] ?? null,'grupo'=>$grp,'descricao'=>$v['description'] ?? null ];
        }
      }
    }
  }
}

// ============ Persistência ============
jsonWrite($dataDir.'/LayoutsData.json',$layoutsData);
jsonWrite($dataDir.'/PaginasData.json',$pagesData);
jsonWrite($dataDir.'/ComponentesData.json',$componentsData);
jsonWrite($dataDir.'/VariaveisData.json',$variablesData);
foreach(['Layouts','Paginas','Componentes','Variaveis'] as $T) { jsonWrite($orphDir.'/'.$T.'Data.json',$orphans[strtolower($T)] ?? []); }

// Remover antigo Data.json único se existir (não mais utilizado)
$legacy = $dataDir.'/Data.json'; if(is_file($legacy)) @unlink($legacy);

$total = count($layoutsData)+count($pagesData)+count($componentsData)+count($variablesData);
$orphTotal = 0; foreach($orphans as $o){ $orphTotal += count($o);} 
echo "Gerados múltiplos Data.json: Layouts=".count($layoutsData)." Paginas=".count($pagesData)." Componentes=".count($componentsData)." Variaveis=".count($variablesData)." | Orphans=$orphTotal | Total=$total\n";

exit(0);
