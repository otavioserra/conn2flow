const fs=require('fs');
const bytes=fs.readFileSync(process.argv[2] || 'temp/req236-css-audit-homolog.json');
const report=JSON.parse(bytes.toString(bytes[0]===255&&bytes[1]===254?'utf16le':'utf8').replace(/^\uFEFF/,''));
const tables={};
for(const [table,data] of Object.entries(report)){const items=data.itens||[];tables[table]={resources:items.length,stale:items.filter(i=>i.stale).length,without_rules:items.filter(i=>i.descobertas>0).length,legacy_markup:items.reduce((sum,i)=>sum+(i.residuos_fomantic||0),0)};}
const admin=(report.paginas?.itens||[]).filter(i=>i.layout_id==='layout-administrativo-tailwind');
const result={command:'php cli/c2f.php css:audit --project=conn2flow-site-local --json',tables,administrative_pages:{count:admin.length,legacy_markup:admin.filter(i=>i.residuos_fomantic).map(i=>({id:i.id,language:i.language,count:i.residuos_fomantic}))},limitations:'Auditoria SQL Tailwind: resíduos visuais excluem selects usados como ganchos da ponte. Classes sem regra e procedência stale incluem recursos públicos, templates e marcadores; não equivalem a classes Fomantic. HTML gerado por PHP/JS exige roteiro de navegador.'};
fs.writeFileSync('sdd/validation/req236-css-report.json',JSON.stringify(result,null,2)+'\n');console.log(JSON.stringify(result,null,2));
