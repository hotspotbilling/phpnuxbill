{if $paginator}
    <nav aria-label="Page navigation pagination-sm">
        <ul class="pagination">
            <li {if empty($paginator['prev'])}class="disabled" {/if}>
                <a href="{$paginator['url']}{$paginator['prev']}" aria-label="Previous">
                    <span aria-hidden="true">prev</span>
                </a>
            </li>
            {foreach $paginator['pages'] as $page}
                <li class="{if $paginator['page'] == $page}active{elseif $page == '...'}disabled{/if}"><a href="{$paginator['url']}{$page}">{$page}</a></li>
            {/foreach}
            <li {if $paginator['count']==$paginator['next']}class="disabled" {/if}>
                <a href="{$paginator['url']}{$paginator['next']}" aria-label="Next">
                    <span aria-hidden="true">next</span>
                </a>
            </li>
        </ul>
    </nav>
{/if}