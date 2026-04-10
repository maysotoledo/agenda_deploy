<x-filament-panels::page>

    <div style="background:#4b5563;color:#fff;text-align:center;font-weight:700;
                padding:12px;border-radius:12px 12px 0 0;letter-spacing:.5px;">
        TELEFONIA MÓVEL — CONTATOS PARA OFÍCIOS / ORDENS
    </div>

    <div style="border:1px solid rgba(0,0,0,.12); border-top:0; border-radius:0 0 12px 12px; padding:20px;">

        <style>
            /* Grid responsivo e estável */
            .tm-grid{
                display:grid;
                gap:18px;
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            @media (min-width: 768px){
                .tm-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (min-width: 1280px){
                .tm-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
            }

            /* Card manual (altura fixa = todos iguais) */
            .tm-card{
                height: 240px; /* ajuste se quiser mais alto/baixo */
                border: 1px solid rgba(0,0,0,.12);
                border-radius: 14px;
                padding: 14px 14px 12px 14px;
                background: rgba(255,255,255,.85);
                display:flex;
                flex-direction:column;
                box-sizing:border-box;
            }

            .tm-title{
                font-size: 20px;
                font-weight: 800;
                margin: 0 0 10px 0;
            }

            .tm-body{
                flex:1;
                overflow:auto;
                line-height:1.75;
                padding-right:6px;
                box-sizing:border-box;
            }

            .tm-body a{
                color:#2563eb;
                text-decoration:underline;
            }

            .tm-body::-webkit-scrollbar{ width:8px; }
            .tm-body::-webkit-scrollbar-thumb{ background: rgba(0,0,0,.18); border-radius:10px; }
            .tm-body::-webkit-scrollbar-track{ background: rgba(0,0,0,.05); border-radius:10px; }

            html.dark .tm-card{
                background: rgba(17,24,39,.65);
                border-color: rgba(255,255,255,.14);
            }
            html.dark .tm-body, html.dark .tm-title{
                color: rgba(255,255,255,.92);
            }
            html.dark .tm-body a{
                color:#60a5fa;
            }
        </style>

        <div class="tm-grid">

            {{-- TIM --}}
            <div class="tm-card">
                <div class="tm-title">TIM</div>
                <div class="tm-body">
                    <div><b>📩 Email:</b> <a href="mailto:graop_oficios@timbrasil.com.br">graop_oficios@timbrasil.com.br</a></div>
                    <div style="margin-top:10px;"><b>📞 Telefone:</b> 11 4251 6633</div>
                </div>
            </div>

            {{-- VIVO --}}
            <div class="tm-card">
                <div class="tm-title">VIVO</div>
                <div class="tm-body">
                    <div><b>📩 Email:</b> <a href="mailto:ordens.sigilo.br@telefonica.com">ordens.sigilo.br@telefonica.com</a></div>
                    <div style="margin-top:10px;"><b>📞 Telefone:</b> 0800 770 8486</div>
                </div>
            </div>

            {{-- CLARO --}}
            <div class="tm-card">
                <div class="tm-title">CLARO</div>
                <div class="tm-body">
                    <div><b>📩 Emails:</b></div>

                    <div style="margin-top:8px;">
                        • <a href="mailto:oficios.doc@claro.com.br">oficios.doc@claro.com.br</a>
                        <div style="opacity:.85; margin-left:14px;">
                            - Para envio de ordens judiciais e ofícios extrajudiciais.
                        </div>
                    </div>

                    <div style="margin-top:10px;">
                        • <a href="mailto:oficios.juridico@claro.com.br">oficios.juridico@claro.com.br</a>
                        <div style="opacity:.85; margin-left:14px;">
                            - Para solicitações de dados cadastrais, bilhetagem e outros (quando a ordem judicial já foi enviada anteriormente).
                        </div>
                    </div>

                    <div style="margin-top:10px;">
                        • <a href="mailto:oficios.co@claro.com.br">oficios.co@claro.com.br</a>
                        <div style="opacity:.85; margin-left:14px;">
                            - Para problemas técnicos de interceptações.
                        </div>
                    </div>

                    <div style="margin-top:12px;"><b>📞 Telefone:</b> 0800 742 2121</div>
                </div>
            </div>

        </div>
    </div>

</x-filament-panels::page>
