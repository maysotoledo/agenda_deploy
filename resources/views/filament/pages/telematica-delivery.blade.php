<x-filament-panels::page>

    <div style="background:#4b5563;color:#fff;text-align:center;font-weight:700;
                padding:12px;border-radius:12px 12px 0 0;letter-spacing:.5px;">
        DELIVERY — CONTATOS
    </div>

    <div style="border:1px solid rgba(0,0,0,.12); border-top:0; border-radius:0 0 12px 12px; padding:20px;">

        <style>
            .dlv-grid{
                display:grid;
                gap:18px;
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            @media (min-width: 768px){ .dlv-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 1280px){ .dlv-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); } }

            .dlv-card{
                height: 330px; /* altura fixa para ficar simétrico */
                border: 1px solid rgba(0,0,0,.12);
                border-radius: 14px;
                padding: 14px;
                background: rgba(255,255,255,.85);
                display:flex;
                flex-direction:column;
                box-sizing:border-box;
            }

            .dlv-logo{
                width:145px;
                height:145px;
                margin:0 auto 10px auto;
                display:flex;
                align-items:center;
                justify-content:center;
            }
            .dlv-logo img{
                width:145px;
                height:145px;
                object-fit:contain;
                border-radius:12px;
                display:block;
            }

            .dlv-title{
                text-align:center;
                font-size:18px;
                font-weight:800;
                margin:0 0 10px 0;
            }

            .dlv-body{
                flex:1;
                overflow:auto;
                line-height:1.75;
                padding-right:6px;
                box-sizing:border-box;
            }

            .dlv-body a{ color:#2563eb; text-decoration:underline; }

            .dlv-body::-webkit-scrollbar{ width:8px; }
            .dlv-body::-webkit-scrollbar-thumb{ background: rgba(0,0,0,.18); border-radius:10px; }
            .dlv-body::-webkit-scrollbar-track{ background: rgba(0,0,0,.05); border-radius:10px; }

            html.dark .dlv-card{ background: rgba(17,24,39,.65); border-color: rgba(255,255,255,.14); }
            html.dark .dlv-title, html.dark .dlv-body{ color: rgba(255,255,255,.92); }
            html.dark .dlv-body a{ color:#60a5fa; }
        </style>

        @php
            $logo = fn (string $file) => asset("storage/telematica/delivery/logos/{$file}");
        @endphp

        <div class="dlv-grid">

            {{-- Pede Aí --}}
            <div class="dlv-card">
                <div class="dlv-logo">
                    <img src="{{ $logo('pedeai.png') }}" alt="Pede Aí" onerror="this.style.display='none'">
                </div>
                <div class="dlv-title">Pede Aí</div>
                <div class="dlv-body">
                    <div><b>📲 WhatsApp:</b> <a href="https://wa.me/5587991078948" target="_blank" rel="noopener">+55 (87) 99107-8948</a></div>
                    <div style="margin-top:10px;"><b>📩 Email:</b> <a href="mailto:contato@pede.ai">contato@pede.ai</a></div>
                </div>
            </div>

            {{-- Zero1 --}}
            <div class="dlv-card">
                <div class="dlv-logo">
                    <img src="{{ $logo('zero1.png') }}" alt="Zero1" onerror="this.style.display='none'">
                </div>
                <div class="dlv-title">Zero1</div>
                <div class="dlv-body">
                    <div style="margin-top:10px;"><b>📲 WhatsApp:</b> <a href="https://wa.me/5569984366692" target="_blank" rel="noopener">+55 (66) 98436-6692</a></div>
                    <div><b>📩 Email:</b> <a href="mailto:atendimento@zero1delivery.com.br">atendimento@zero1delivery.com.br</a></div>
                </div>
            </div>

        </div>
    </div>

</x-filament-panels::page>
