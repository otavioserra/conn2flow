Biblioteca de integração PagSeguro para PHP
===========================================

[![Build Status](https://travis-ci.org/pagseguro/php.png?branch=master)](https://travis-ci.org/pagseguro/php)

Descrição
---------

A biblioteca PagSeguro em PHP é um conjunto de classes de domínio que facilitam, para o desenvolvedor PHP, a utilização das funcionalidades que o PagSeguro oferece na forma de APIs. Com a biblioteca instalada e configurada, você pode facilmente integrar funcionalidades como:

 - Criar [requisições de pagamentos]
 - Consultar [transações por código]
 - Consultar [transações por intervalo de datas]
 - Consultar [transações abandonadas]
 - Receber [notificações]


Requisitos
----------

 - [PHP] 5.1.6+
 - [SPL]
 - [cURL]
 - [DOM]


Instalação
----------

 - Baixe o repositório como arquivo zip ou faça um clone;
 - Descompacte os arquivos em seu computador;
 - Dentro do diretório *source* existem dois diretórios, o *examples* e o *PagSeguroLibrary*. O diretório *examples* contém exemplos de chamadas utilizando a API e o diretório *PagSeguroLibrary* contém a biblioteca propriamente dita. Caso queira importar somente a biblioteca, faça upload do diretório *PagSeguroLibrary* e inclua a classe *PagSeguroLibrary.php* em seu projeto. Essa classe se encarregará de importar todas as funcionalidades da biblioteca no seu sistema.

Alternativamente é possível utilizar o Composer para carregar a biblioteca (pagseguro/php).


Configuração
------------

Para fazer uso real da biblioteca é preciso fazer algumas configurações no arquivo 'PagSeguroConfig.php', que encontra-se no diretório 'config'. As opções disponíveis estão descritas abaixo.

 - **environment**: no momento aceita apenas o valor *production*.
 - **email**: e-mail cadastrado no PagSeguro.
 - **token**: token gerado no PagSeguro.
 - **charset**: codificação do seu sistema (ISO-8859-1 ou UTF-8).
 - **log**: ativa/desativa a geração de logs.
 - **fileLocation**: local onde se deseja criar o arquivo de log. Ex.: /logs/ps.log.

Mais informações estão disponíveis na [documentação oficial].


Changelog
---------

2.2.1
 - Ajuste no tratamento do XML retornado em requisições feitas ao PagSeguro

2.2.0
 - Ao registrar a transação é possível obter apenas o código do checkout para utilização em um checkout lightbox (ver exemplo createPaymentRequestLightbox.php). Obs.: Esta funcionalidade ainda não está disponível comercialmente para todos os vendedores.

2.1.9
 - Melhorias "estéticas" no código, como identação/formatação.

2.1.8

 - Atualização dos códigos de meios de pagamento.
 - Melhoria na identificação dos requisitos mínimos para funcionamento.
 - Melhoria no tratamento de dados relacionados a endereço.

2.1.7

 - Adicionado: Classes para manipulação de metadados e parâmetros genéricos.

2.1.6

 - Adicionado: Classe para manipulação de documentos do sender, ex. CPF.

2.1.5

 - Adicionado: Funcionalidade de envio de url de notificação via requisição de checkout.
 - Adicionado: Novas funcionalidades para tratamento de dados no helper.
 - Correção: Tratamento de duplo espaço no sender name da requisição de checkout.
 
2.1.4

 - Adicionado: Classe para manipulação de moedas permitidas nas transações com o PagSeguro.

2.1.3

 - Correção: A requisição era abortada se a geração de log estivesse ativa e o usuário não possuisse arquivo para geração de log nem permissão de escrita e leitura para o arquivo.

2.0.0 - 2.1.2

 - Classes de domínios que representam pagamentos, notificações e transações.
 - Criação de checkouts via API.
 - Controller para processar notificações de pagamento enviadas pelo PagSeguro.
 - Módulo de consulta de transações.


Licença
-------

Copyright 2013 PagSeguro Internet LTDA.

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.


Notas
-----

 - O PagSeguro somente aceita pagamento utilizando a moeda Real brasileiro (BRL).
 - Certifique-se que o email e o token informados estejam relacionados a uma conta que possua o perfil de vendedor ou empresarial.
 - Certifique-se que tenha definido corretamente o charset de acordo com a codificação (ISO-8859-1 ou UTF-8) do seu sistema. Isso irá prevenir que as transações gerem possíveis erros ou quebras ou ainda que caracteres especiais possam ser apresentados de maneira diferente do habitual.
 - Para que ocorra normalmente a geração de logs, certifique-se que o diretório e o arquivo de log tenham permissões de leitura e escrita.


[Dúvidas?]
----------

Em caso de dúvidas mande um e-mail para desenvolvedores@pagseguro.com.br


Contribuições
-------------

Achou e corrigiu um bug ou tem alguma feature em mente e deseja contribuir?

* Faça um fork.
* Adicione sua feature ou correção de bug.
* Envie um pull request no [GitHub].


  [requisições de pagamentos]: https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-pagamentos.html
  [notificações]: https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-notificacoes.html
  [transações por código]: https://pagseguro.uol.com.br/v2/guia-de-integracao/consulta-de-transacoes-por-codigo.html
  [transações por intervalo de datas]: https://pagseguro.uol.com.br/v2/guia-de-integracao/consulta-de-transacoes-por-intervalo-de-datas.html
  [transações abandonadas]: https://pagseguro.uol.com.br/v2/guia-de-integracao/consulta-de-transacoes-abandonadas.html
  [Dúvidas?]: https://pagseguro.uol.com.br/desenvolvedor/comunidade.jhtml
  [PHP]: http://www.php.net/
  [SPL]: http://php.net/manual/en/book.spl.php
  [cURL]: http://php.net/manual/en/book.curl.php
  [DOM]: http://php.net/manual/en/book.dom.php
  [GitHub]: https://github.com/pagseguro/php/
  [documentação oficial]: https://pagseguro.uol.com.br/v2/guia-de-integracao/tutorial-da-biblioteca-pagseguro-em-php.html
