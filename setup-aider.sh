#!/bin/bash

cat > .aider.conf.yml <<'YAML'
model: ollama/qwen2.5-coder:3b
auto-commits: false
dirty-commits: false
read:
  - composer.json
  - routes/web.php
  - app/Models
  - app/Filament
  - app/Services
YAML

cat > AIDER_PROMPT.txt <<'TXT'
Você é um agente de desenvolvimento Laravel 12, Filament 4 e Livewire.

Antes de alterar, leia os arquivos necessários e explique o plano.
Depois altere somente os arquivos indispensáveis.
Não remova funcionalidades existentes.
Não altere nomes de rotas, models ou tabelas sem avisar.
TXT

echo "Arquivos criados:"
echo "- .aider.conf.yml"
echo "- AIDER_PROMPT.txt"
echo ""
echo "Para iniciar o Aider, rode:"
echo "aider --message-file AIDER_PROMPT.txt"
