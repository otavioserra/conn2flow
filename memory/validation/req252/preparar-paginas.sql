INSERT INTO paginas (id_usuarios,layout_id,nome,id,language,caminho,tipo,modulo,opcao,raiz,sem_permissao,html,status,versao,framework_css,project)
 SELECT id_usuarios,layout_id,'Roteiro REQ-252 grade','roteiro-req-252-grade',language,'roteiro-req-252-grade/',tipo,modulo,opcao,raiz,sem_permissao,
 '<section style="max-width:1280px;margin:0 auto;padding:32px 16px"><!-- widgets#dashboard->render({"grupo_slug":"roteiro-req-252-grade","id":"roteiro-req-252-grade"}) < --><!-- widgets#dashboard->render({"grupo_slug":"roteiro-req-252-grade","id":"roteiro-req-252-grade"}) > --></section>',
 'A',1,framework_css,project FROM paginas p WHERE p.id='plataforma' AND p.language='pt-br' AND NOT EXISTS (SELECT 1 FROM paginas x WHERE x.id='roteiro-req-252-grade');
INSERT INTO paginas (id_usuarios,layout_id,nome,id,language,caminho,tipo,modulo,opcao,raiz,sem_permissao,html,status,versao,framework_css,project)
 SELECT id_usuarios,layout_id,'Roteiro REQ-252 lousa','roteiro-req-252-lousa',language,'roteiro-req-252-lousa/',tipo,modulo,opcao,raiz,sem_permissao,
 '<section style="max-width:1280px;margin:0 auto;padding:32px 16px"><!-- widgets#dashboard->render({"grupo_slug":"roteiro-req-252-lousa","id":"roteiro-req-252-lousa"}) < --><!-- widgets#dashboard->render({"grupo_slug":"roteiro-req-252-lousa","id":"roteiro-req-252-lousa"}) > --></section>',
 'A',1,framework_css,project FROM paginas p WHERE p.id='plataforma' AND p.language='pt-br' AND NOT EXISTS (SELECT 1 FROM paginas x WHERE x.id='roteiro-req-252-lousa');
