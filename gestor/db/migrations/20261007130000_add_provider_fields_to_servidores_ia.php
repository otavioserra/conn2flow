<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * REQ-260: cada servidor de IA guarda o endereço base e os modelos de texto e de imagem.
 * Vazio usa o padrão do provedor (`ia-provedores.php`); o endereço é obrigatório só no tipo compatível com OpenAI.
 */
final class AddProviderFieldsToServidoresIa extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('servidores_ia');
        $alterou = false;
        foreach (['url_base' => 255, 'modelo' => 150, 'modelo_imagem' => 150] as $coluna => $limite) {
            if ($table->hasColumn($coluna)) {
                continue;
            }
            $table->addColumn($coluna, 'string', ['limit' => $limite, 'null' => true]);
            $alterou = true;
        }
        if ($alterou) {
            $table->update();
        }
    }
}
