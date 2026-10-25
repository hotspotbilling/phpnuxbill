{include file="sections/header.tpl"}

<style>
    /* 1. WARNA TEMA SIDEBAR (Disesuaikan dari Foto 2) */
    :root {
        --sidebar-bg: #2b344a; 
        --accent-purple: #3f4095; 
        --border-purple: #8e44ad;
    }

    /* Info Github - Warna Sidebar Dark */
    .custom-alert-sidebar {
        background: var(--sidebar-bg);
        border-left: 5px solid var(--border-purple);
        color: white;
        padding: 15px;
        margin-bottom: 20px;
    }
    .custom-alert-sidebar a { color: #ffcc00; font-weight: bold; }

    /* Panel Pemasang - Kotak Tegas No Radius */
    .panel-sidebar {
        border: 1px solid var(--sidebar-bg);
        border-radius: 0 !important;
        margin-bottom: 25px;
    }
    .panel-sidebar .panel-heading {
        background: var(--sidebar-bg) !important;
        color: white !important;
        border-radius: 0 !important;
        border: none;
    }

    /* 2. KOTAK PLUGIN COMPACT */
    .plugin-card {
        border: 1px solid var(--border-purple); 
        border-radius: 4px;
        background: #fff;
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
        height: 260px; 
        overflow: hidden;
    }
    .plugin-header { padding: 8px 12px; border-bottom: 1px solid #eee; background: #fcfcfc; }
    .plugin-title { font-size: 14px; font-weight: bold; color: var(--sidebar-bg); margin: 0; }
    .plugin-body { padding: 10px; flex-grow: 1; overflow-y: auto; font-size: 12px; color: #666; }
    .plugin-footer { display: flex; border-top: 1px solid #eee; }
    .plugin-footer .btn { flex: 1; border-radius: 0; margin: 0; border: none; padding: 8px 5px; font-size: 11px; }

    /* 3. PAGINATION PROFESIONAL */
    .pagination-container { text-align: center; margin: 20px 0; }
    .page-btn { 
        margin: 0 2px; 
        border-radius: 2px; 
        border: 1px solid #ddd;
        background: #fff;
        padding: 4px 10px;
        cursor: pointer;
    }
    .page-btn.active { background: var(--border-purple); color: white; border-color: var(--border-purple); }
</style>

{if empty($_c['github_token'])}
    <div class="custom-alert-sidebar">
        <i class="glyphicon glyphicon-info-sign"></i> {Lang::T('To download from private/paid repository')}, 
        <a href="{Text::url('')}settings/app#GithubAuthentication">{Lang::T('Set your Github Authentication first')}</a>
    </div>
{/if}

<div class="panel panel-sidebar">
    <div class="panel-heading">
        <h3 class="panel-title">{Lang::T('Plugin Installer')}</h3>
    </div>
    <div class="panel-body">
        <form method="post" enctype="multipart/form-data" action="{Text::url('')}pluginmanager/dlinstall">
            <div class="row">
                <div class="col-md-5">
                    <label class="small">File Zip</label>
                    <input type="file" name="zip_plugin" accept="application/zip" class="form-control input-sm" onchange="this.submit()">
                </div>
                <div class="col-md-5">
                    <label class="small">Github URL</label>
                    <input type="url" class="form-control input-sm" name="gh_url" placeholder="https://github.com/...">
                </div>
                <div class="col-md-2">
                    <br><button type="submit" class="btn btn-primary btn-sm btn-block" style="border-radius:0; background: var(--accent-purple);">Pasang</button>
                </div>
            </div>
        </form>
    </div>
</div>

<ul class="nav nav-tabs" role="tablist">
    <li class="active"><a href="#tab-plugin" data-toggle="tab">Plugin</a></li>
    <li><a href="#tab-pg" data-toggle="tab">Payment Gateway</a></li>
    <li><a href="#tab-device" data-toggle="tab">Devices</a></li>
</ul>

<div class="tab-content" style="padding-top: 15px;">
    {function name="render_grid" items=[] type="" id=""}
        <div role="tabpanel" class="tab-pane {if $id == 'tab-plugin'}active{/if}" id="{$id}">
            <div class="row list-container">
                {foreach $items as $item}
                    <div class="col-md-4 col-sm-6 item-card">
                        <div class="plugin-card">
                            <div class="plugin-header">
                                <span class="label {if $item['ispaid']}label-warning{else}label-success{/if} pull-right" style="font-size:9px;">
                                    {if $item['ispaid']}Premium{else}Free{/if}
                                </span>
                                <h3 class="plugin-title">{$item['name']}</h3>
                            </div>
                            <div class="plugin-body">{$item['description']}</div>
                            <div class="plugin-footer">
                                <a href="{$item['url']}" target="_blank" class="btn btn-default" title="Web"><i class="glyphicon glyphicon-globe"></i></a>
                                <a href="{$item['github']}" target="_blank" class="btn btn-info" title="Source"><i class="glyphicon glyphicon-align-left"></i></a>
                                <a href="{Text::url('')}pluginmanager/delete/{$type}/{$item['id']}" onclick="return ask(this, 'Delete?')" class="btn btn-danger"><i class="glyphicon glyphicon-trash"></i></a>
                                <a href="{Text::url('')}pluginmanager/install/{$type}/{$item['id']}" class="btn btn-success"><i class="glyphicon glyphicon-download"></i> Install</a>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
            <div class="pagination-area pagination-container"></div>
        </div>
    {/function}

    {call name="render_grid" items=$plugins type="plugin" id="tab-plugin"}
    {call name="render_grid" items=$pgs type="payment" id="tab-pg"}
    {call name="render_grid" items=$dvcs type="device" id="tab-device"}
</div>

{literal}
<script>
document.addEventListener("DOMContentLoaded", function() {
    const itemsPerPage = 5;

    function setupPagination(tabId) {
        const container = document.querySelector('#' + tabId);
        const items = Array.from(container.querySelectorAll('.item-card'));
        const paginationWrapper = container.querySelector('.pagination-area');
        const totalPages = Math.ceil(items.length / itemsPerPage);

        if (totalPages <= 1) return;

        function renderPagination(currentPage) {
            paginationWrapper.innerHTML = '';
            
            // Logika Pagination Dinamis (Maks 5 angka)
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }

            // Tombol First/Prev <<
            if (currentPage > 1) {
                createBtn('<<', 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i > 0) createBtn(i, i, i === currentPage);
            }

            // Tombol Next/Last >>
            if (currentPage < totalPages) {
                createBtn('>>', totalPages);
            }
        }

        function createBtn(text, page, isActive = false) {
            let btn = document.createElement('button');
            btn.innerText = text;
            btn.className = "page-btn " + (isActive ? "active" : "");
            btn.onclick = function() {
                showPage(page);
                renderPagination(page);
                // Scroll ke atas container
                window.scrollTo({ top: container.offsetTop - 70, behavior: 'smooth' });
            };
            paginationWrapper.appendChild(btn);
        }

        function showPage(page) {
            items.forEach((item, idx) => {
                item.style.display = (idx >= (page-1)*itemsPerPage && idx < page*itemsPerPage) ? "block" : "none";
            });
        }

        renderPagination(1);
        showPage(1);
    }

    setupPagination('tab-plugin');
    setupPagination('tab-pg');
    setupPagination('tab-device');
});
</script>
{/literal}

{include file="sections/footer.tpl"}
