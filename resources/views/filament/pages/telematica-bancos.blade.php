<x-filament-panels::page>

    <div style="background:#4b5563;color:#fff;text-align:center;font-weight:700;
                padding:12px;border-radius:12px 12px 0 0;letter-spacing:.5px;">
        BANCOS / INSTITUIÇÕES FINANCEIRAS — CONTATOS PARA OFÍCIOS / ORDENS
    </div>

    <div style="border:1px solid rgba(0,0,0,.12); border-top:0; border-radius:0 0 12px 12px; padding:20px;">

        <style>
            .bk-grid{
                display:grid;
                gap:18px;
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            @media (min-width: 768px){ .bk-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 1280px){ .bk-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); } }

            .bk-card{
                height: 360px; /* um pouco maior por causa do logo 145x145 */
                border: 1px solid rgba(0,0,0,.12);
                border-radius: 14px;
                padding: 14px;
                background: rgba(255,255,255,.85);
                display:flex;
                flex-direction:column;
                box-sizing:border-box;
            }

            /* ✅ LOGO 145x145 centralizado */
            .bk-logo{
                width: 145px;
                height: 145px;
                margin: 0 auto 10px auto;
                display:flex;
                align-items:center;
                justify-content:center;
            }
            .bk-logo img{
                width: 145px;
                height: 145px;
                object-fit: contain;
                border-radius: 12px;
                display:block;
            }

            /* ✅ título centralizado */
            .bk-title{
                text-align:center;
                font-size: 18px;
                font-weight: 800;
                margin: 0 0 10px 0;
            }

            .bk-body{
                flex:1;
                overflow:auto;
                line-height:1.75;
                padding-right:6px;
                box-sizing:border-box;
            }

            .bk-body a{ color:#2563eb; text-decoration:underline; }

            .bk-body::-webkit-scrollbar{ width:8px; }
            .bk-body::-webkit-scrollbar-thumb{ background: rgba(0,0,0,.18); border-radius:10px; }
            .bk-body::-webkit-scrollbar-track{ background: rgba(0,0,0,.05); border-radius:10px; }

            html.dark .bk-card{ background: rgba(17,24,39,.65); border-color: rgba(255,255,255,.14); }
            html.dark .bk-title, html.dark .bk-body{ color: rgba(255,255,255,.92); }
            html.dark .bk-body a{ color:#60a5fa; }
        </style>

        @php
            $logo = fn (string $file) => asset("storage/telematica/bancos/logos/{$file}");
        @endphp

        <div class="bk-grid">

            {{-- Banco do Brasil --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('banco-do-brasil.png') }}" alt="Banco do Brasil" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Banco do Brasil</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:cenopserv.oficioscwb@bb.com.br">cenopserv.oficioscwb@bb.com.br</a></div>
                    <div>📩 <a href="mailto:oficiosjudiciais@bb.com.br">oficiosjudiciais@bb.com.br</a></div>
                </div>
            </div>

            {{-- Bradesco --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('bradesco.png') }}" alt="Bradesco" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Bradesco</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:oficiosjudiciais@bradesco.com.br">oficiosjudiciais@bradesco.com.br</a></div>
                    <div>📩 <a href="mailto:4040.oficios@bradesco.com.br">4040.oficios@bradesco.com.br</a></div>
                </div>
            </div>

            {{-- C6 Bank --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('c6.png') }}" alt="C6 Bank" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">C6 Bank</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:oficiosbacen@c6bank.com.br">oficiosbacen@c6bank.com.br</a></div>
                </div>
            </div>

            {{-- Caixa Econômica Federal --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('caixa.png') }}" alt="Caixa" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Caixa Econômica Federal</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:simba@caixa.gov.br">simba@caixa.gov.br</a></div>
                    <div>📩 <a href="mailto:ceseg14@caixa.gov.br">ceseg14@caixa.gov.br</a> (ref. fraudes eletrônicas)</div>
                </div>
            </div>

            {{-- Banco Inter S.A. --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('inter.png') }}" alt="Banco Inter" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Banco Inter S.A.</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:itaujudicial@itau-unibanco.com.br">itaujudicial@itau-unibanco.com.br</a></div>
                    <div>📩 <a href="mailto:documento@itau-unibanco.com.br">documento@itau-unibanco.com.br</a></div>
                </div>
            </div>

            {{-- Itaú --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('itau.png') }}" alt="Itaú" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Itaú</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:itaujudicial@itau-unibanco.com.br">itaujudicial@itau-unibanco.com.br</a></div>
                    <div>📩 <a href="mailto:documento@itau-unibanco.com.br">documento@itau-unibanco.com.br</a></div>
                </div>
            </div>

            {{-- Nubank --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('nubank.png') }}" alt="Nubank" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Nubank</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:oficios@nu.com.br">oficios@nu.com.br</a></div>
                    <div style="margin-top:10px;">📲 Plataforma PRAJA:
                        <a href="https://nubank.com.br/transparencia/praja" target="_blank" rel="noopener">acessar ↗️</a>
                    </div>
                </div>
            </div>

            {{-- Santander --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('santander.png') }}" alt="Santander" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Santander</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:simba@brb.com.br">simba@brb.com.br</a></div>
                </div>
            </div>

            {{-- Sicoob --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('sicoob.png') }}" alt="Sicoob" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Sicoob</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:quebra_sigilo@sicoob.com.br">quebra_sigilo@sicoob.com.br</a></div>
                    <div>📩 <a href="mailto:atendimentopolicia@sicoob.com.br">atendimentopolicia@sicoob.com.br</a></div>
                </div>
            </div>

            {{-- Sicredi --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('sicredi.png') }}" alt="Sicredi" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Sicredi</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:jud_oficios@sicredi.com.br">jud_oficios@sicredi.com.br</a></div>
                </div>
            </div>

            {{-- Ame Digital --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('ame.png') }}" alt="Ame Digital" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Ame Digital</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:daniela.baker@b2wdigital.com">daniela.baker@b2wdigital.com</a></div>
                    <div>📩 <a href="mailto:edison.alvares@lasa.com.br">edison.alvares@lasa.com.br</a></div>
                </div>
            </div>

            {{-- Mercado Pago --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('mercado-pago.png') }}" alt="Mercado Pago" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Mercado Pago</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:oficios@mercadolivre.com">oficios@mercadolivre.com</a></div>
                </div>
            </div>

            {{-- PagBank --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('pagbank.png') }}" alt="PagBank" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">PagBank</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:intimauol@uolinc.com">intimauol@uolinc.com</a></div>
                </div>
            </div>

            {{-- PayPal --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('paypal.png') }}" alt="PayPal" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">PayPal</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:lawenforcement@paypal.com">lawenforcement@paypal.com</a></div>
                    <div>📩 <a href="mailto:cboschiero@paypal.com">cboschiero@paypal.com</a></div>
                </div>
            </div>

            {{-- PicPay --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('picpay.png') }}" alt="PicPay" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">PicPay</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:oficios@picpay.com">oficios@picpay.com</a></div>
                </div>
            </div>

            {{-- PixtoPay --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('pixtopay.png') }}" alt="PixtoPay" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">PixtoPay</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:juridico@pixtopay.com.br">juridico@pixtopay.com.br</a></div>
                </div>
            </div>

            {{-- InfinitePay (CloudWalk) --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('infinitepay.png') }}" alt="InfinitePay / CloudWalk" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">InfinitePay (CloudWalk)</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:oficios@cloudwalk.io">oficios@cloudwalk.io</a></div>
                    <div>📩 <a href="mailto:legal@cloudwalk.io">legal@cloudwalk.io</a></div>
                </div>
            </div>

            {{-- Stone --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('stone.png') }}" alt="Stone" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Stone</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:ccrim@stone.com.br">ccrim@stone.com.br</a></div>
                </div>
            </div>

            {{-- SumUp --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('sumup.png') }}" alt="SumUp" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">SumUp</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:oficios@sumup.com.br">oficios@sumup.com.br</a></div>
                </div>
            </div>

            {{-- Cielo --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('cielo.png') }}" alt="Cielo" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Cielo</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:oficios.notificacoes@cielo.com.br">oficios.notificacoes@cielo.com.br</a></div>
                    <div>📩 <a href="mailto:contencioso@cielo.com.br">contencioso@cielo.com.br</a></div>
                </div>
            </div>

            {{-- Mastercard --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('mastercard.png') }}" alt="Mastercard" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Mastercard</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:juridicobr@mastercard.com">juridicobr@mastercard.com</a></div>
                    <div>📩 <a href="mailto:contencioso@cielo.com.br">contencioso@cielo.com.br</a></div>
                </div>
            </div>

            {{-- Visa --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('visa.png') }}" alt="Visa" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Visa</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:visa.cadastro@mtostes.com.br">visa.cadastro@mtostes.com.br</a></div>
                    <div>📩 <a href="mailto:brfinance@visa.com">brfinance@visa.com</a></div>
                </div>
            </div>

            {{-- Elo --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('elo.png') }}" alt="Elo" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Elo</div>
                <div class="bk-body">
                    <div>📩 <a href="mailto:notificaelo@elo.com.br">notificaelo@elo.com.br</a></div>
                </div>
            </div>

            {{-- Banco Central --}}
            <div class="bk-card">
                <div class="bk-logo">
                    <img src="{{ $logo('banco-central.png') }}" alt="Banco Central" onerror="this.style.display='none'">
                </div>
                <div class="bk-title">Banco Central</div>
                <div class="bk-body">
                    <div>ℹ️ Contatos oficiais das instituições financeiras autorizadas (nem todos são para recebimento de ofícios):</div>
                    <div style="margin-top:10px;">
                        <a href="https://www.bcb.gov.br/estabilidadefinanceira/relacao_instituicoes_funcionamento" target="_blank" rel="noopener">
                            Acessar página ↗️
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

</x-filament-panels::page>
