<x-filament-panels::page>

    <div style="background:#4b5563;color:#fff;text-align:center;font-weight:700;
                padding:12px;border-radius:12px 12px 0 0;letter-spacing:.5px;">
        TELEFONIA MÓVEL — CONTATOS PARA OFÍCIOS / ORDENS
    </div>

    <div style="border:1px solid rgba(0,0,0,.12); border-top:0; border-radius:0 0 12px 12px; padding:20px;">

        <style>
            .tm-grid{
                display:grid;
                gap:18px;
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            @media (min-width: 768px){ .tm-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 1280px){ .tm-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); } }

            .tm-card{
                height: 360px; /* altura fixa (cards simétricos) */
                border: 1px solid rgba(0,0,0,.12);
                border-radius: 14px;
                padding: 14px;
                background: rgba(255,255,255,.85);
                display:flex;
                flex-direction:column;
                box-sizing:border-box;
            }

            /* logo 145x145 centralizado */
            .tm-logo{
                width:145px;
                height:145px;
                margin:0 auto 10px auto;
                display:flex;
                align-items:center;
                justify-content:center;
            }
            .tm-logo img{
                width:145px;
                height:145px;
                object-fit:contain;
                border-radius:12px;
                display:block;
            }

            /* nome centralizado */
            .tm-title{
                text-align:center;
                font-size:18px;
                font-weight:800;
                margin:0 0 10px 0;
            }

            .tm-body{
                flex:1;
                overflow:auto;
                line-height:1.75;
                padding-right:6px;
                box-sizing:border-box;
            }

            .tm-body a{ color:#2563eb; text-decoration:underline; }

            .tm-body::-webkit-scrollbar{ width:8px; }
            .tm-body::-webkit-scrollbar-thumb{ background: rgba(0,0,0,.18); border-radius:10px; }
            .tm-body::-webkit-scrollbar-track{ background: rgba(0,0,0,.05); border-radius:10px; }

            html.dark .tm-card{ background: rgba(17,24,39,.65); border-color: rgba(255,255,255,.14); }
            html.dark .tm-title, html.dark .tm-body{ color: rgba(255,255,255,.92); }
            html.dark .tm-body a{ color:#60a5fa; }
        </style>

        @php
            $logo = fn (string $file) => asset("storage/telematica/telefonia-movel/logos/{$file}");
        @endphp

        <div class="tm-grid">

            {{-- TIM --}}
            <div class="tm-card">
                <div class="tm-logo">
                    <img src="{{ $logo('tim.png') }}" alt="TIM" onerror="this.style.display='none'">
                </div>
                <div class="tm-title">TIM</div>
                <div class="tm-body">
                    <div><b>📩 Email:</b> <a href="mailto:graop_oficios@timbrasil.com.br">graop_oficios@timbrasil.com.br</a></div>
                    <div style="margin-top:10px;"><b>📞 Telefone:</b> 11 4251 6633</div>
                </div>
            </div>

            {{-- VIVO --}}
            <div class="tm-card">
                <div class="tm-logo">
                    <img src="{{ $logo('vivo.png') }}" alt="VIVO" onerror="this.style.display='none'">
                </div>
                <div class="tm-title">VIVO</div>
                <div class="tm-body">
                    <div><b>📩 Email:</b> <a href="mailto:ordens.sigilo.br@telefonica.com">ordens.sigilo.br@telefonica.com</a></div>
                    <div style="margin-top:10px;"><b>📞 Telefone:</b> 0800 770 8486</div>
                </div>
            </div>

            {{-- CLARO --}}
            <div class="tm-card">
                <div class="tm-logo">
                    <img src="{{ $logo('claro.png') }}" alt="CLARO" onerror="this.style.display='none'">
                </div>
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

            {{-- Consulta operadora - abrtelecom --}}
            <div class="tm-card">
                <div class="tm-logo">
                    <img src="{{ $logo('abrtelecom.png') }}" alt="abrtelecom" onerror="this.style.display='none'">
                </div>
                <div class="tm-title">Consulta operadora — abrtelecom</div>
                <div class="tm-body">
                    <a href="https://consultanumero.abrtelecom.com.br/consultanumero/consulta/consultaSituacaoAtualCtg"
                       target="_blank" rel="noopener">
                        📶 Consulta operadora ↗️
                    </a>
                </div>
            </div>

        </div>
    </div>

</x-filament-panels::page>
