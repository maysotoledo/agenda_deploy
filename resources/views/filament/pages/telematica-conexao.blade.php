<x-filament-panels::page>

    <div style="background:#4b5563;color:#fff;text-align:center;font-weight:700;
                padding:12px;border-radius:12px 12px 0 0;letter-spacing:.5px;">
        CONEXÃO — CONTATOS PARA OFÍCIOS / ORDENS
    </div>

    <div style="border:1px solid rgba(0,0,0,.12); border-top:0; border-radius:0 0 12px 12px; padding:20px;">

        <style>
            /* Grid responsivo e estável */
            .cx-grid{
                display:grid;
                gap:18px;
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            @media (min-width: 768px){
                .cx-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (min-width: 1280px){
                .cx-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
            }

            /* Card “manual” (não depende do x-filament::section) */
            .cx-card{
                height: 260px; /* ✅ todos iguais */
                border: 1px solid rgba(0,0,0,.12);
                border-radius: 14px;
                padding: 14px 14px 12px 14px;
                background: rgba(255,255,255,.85);
                display:flex;
                flex-direction:column;
                box-sizing:border-box;
            }

            /* Título fixo */
            .cx-title{
                font-size: 20px;
                font-weight: 800;
                margin: 0 0 10px 0;
            }

            /* Conteúdo rolável */
            .cx-body{
                flex:1;
                overflow:auto;
                line-height:1.75;
                padding-right:6px;
                box-sizing:border-box;
            }

            .cx-body a{
                color:#2563eb;
                text-decoration:underline;
            }

            /* Scrollbar discreta */
            .cx-body::-webkit-scrollbar{ width:8px; }
            .cx-body::-webkit-scrollbar-thumb{ background: rgba(0,0,0,.18); border-radius:10px; }
            .cx-body::-webkit-scrollbar-track{ background: rgba(0,0,0,.05); border-radius:10px; }

            /* Dark mode (quando o Filament estiver em tema escuro) */
            html.dark .cx-card{
                background: rgba(17,24,39,.65);
                border-color: rgba(255,255,255,.14);
            }
            html.dark .cx-body, html.dark .cx-title{
                color: rgba(255,255,255,.92);
            }
            html.dark .cx-body a{
                color:#60a5fa;
            }
        </style>

        <div class="cx-grid">

            {{-- STARLINK --}}
            <div class="cx-card">
                <div class="cx-title">STARLINK</div>
                <div class="cx-body">
                    <div><b>📩 Contato:</b></div>
                    <div><a href="mailto:starlinklawenforcement@spacex.com">starlinklawenforcement@spacex.com</a></div>
                    <div><a href="mailto:walter.coursol@spacex.com">walter.coursol@spacex.com</a></div>

                    <div style="margin-top:10px;"><b>📲 Emergencial:</b></div>
                    <div><a href="mailto:starlinklawenforcementexigent@spacex.com">starlinklawenforcementexigent@spacex.com</a></div>
                </div>
            </div>

            {{-- CIDADE INTERNET --}}
            <div class="cx-card">
                <div class="cx-title">CIDADE INTERNET</div>
                <div class="cx-body">
                    <div><b>📩 Email:</b> <a href="mailto:atendimento.cidadei@gmail.com">atendimento.cidadei@gmail.com</a></div>
                </div>
            </div>

            {{-- AMTECK --}}
            <div class="cx-card">
                <div class="cx-title">AMTECK</div>
                <div class="cx-body">
                    <div><b>📩 Email:</b> <a href="mailto:suporte@amteck.com.br">suporte@amteck.com.br</a></div>
                </div>
            </div>

            {{-- NORTENET --}}
            <div class="cx-card">
                <div class="cx-title">NORTENET</div>
                <div class="cx-body">
                    <div><b>📩 Email:</b> <a href="mailto:gerencia-adm@nortenettelecom.com.br">gerencia-adm@nortenettelecom.com.br</a></div>
                </div>
            </div>

            {{-- SIMPLES INTERNET --}}
            <div class="cx-card">
                <div class="cx-title">SIMPLES INTERNET</div>
                <div class="cx-body">
                    <div><b>📩 Email:</b> <a href="mailto:contato@simplesinternet.net.br">contato@simplesinternet.net.br</a></div>
                </div>
            </div>

            {{-- BASI TELECOM --}}
            <div class="cx-card">
                <div class="cx-title">BASI TELECOM</div>
                <div class="cx-body">
                    <div><b>📲 Telefones:</b></div>
                    <div>• (66) 98433-2943 — Confresa</div>
                    <div>• (66) 98449-9891 — Vila Rica</div>

                    <div style="margin-top:8px;"><b>📩 Email:</b> <a href="mailto:contato@basitelecom.com.br">contato@basitelecom.com.br</a></div>

                    <div style="margin-top:8px;"><b>📍 Endereços:</b></div>
                    <div>• Confresa: Av. Canaã Nº 190, Centro.</div>
                    <div>• Vila Rica: Av. Perimetral Sul, Setor Sul, Nº 253.</div>
                </div>
            </div>

            {{-- SSF TELECOM --}}
            <div class="cx-card">
                <div class="cx-title">SSF TELECOM</div>
                <div class="cx-body">
                    <div><b>📲 Telefone:</b> (66) 9 8429-9315</div>
                    <div style="margin-top:8px;"><b>📩 Email:</b> <a href="mailto:contato@ssftelecom.net">contato@ssftelecom.net</a></div>
                    <div style="margin-top:8px;"><b>📍 Local:</b> Vila Rica - MT</div>
                </div>
            </div>

            {{-- CHIIPNET --}}
            <div class="cx-card">
                <div class="cx-title">CHIIPNET</div>
                <div class="cx-body">
                    <div><b>📍 Endereço:</b> R. Castelo Branco, 20 - Centro, Confresa - MT, 78652-000</div>
                    <div style="margin-top:8px;"><b>📲 Telefone:</b> (66) 98425-4848</div>
                    <div style="margin-top:8px;"><b>📩 Email:</b> <a href="mailto:chiipnet@gmail.com">chiipnet@gmail.com</a></div>
                </div>
            </div>

            {{-- NetWireless Internet --}}
            <div class="cx-card">
                <div class="cx-title">NetWireless Internet</div>
                <div class="cx-body">
                    <div><b>📍 Endereço:</b> R. Iporá - Confresa, MT, 78652-000</div>
                    <div style="margin-top:8px;"><b>📲 Telefones:</b> (66) 98430-3977 — (66) 98437-0893</div>
                    <div style="margin-top:8px;"><b>📩 Email:</b> <a href="mailto:contatos@netwirelessmt.com.br">contatos@netwirelessmt.com.br</a></div>
                </div>
            </div>

            {{-- Plugnet Telecom --}}
            <div class="cx-card">
                <div class="cx-title">Plugnet Telecom</div>
                <div class="cx-body">
                    <div><b>📍 Endereço:</b> R. da América - Centro, Confresa - MT, 78652-000</div>
                    <div style="margin-top:8px;"><b>📲 Telefone:</b> (66) 98429-0606</div>
                </div>
            </div>

        </div>
    </div>

</x-filament-panels::page>
