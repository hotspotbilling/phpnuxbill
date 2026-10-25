{* Star button. Param: x (store item with watch_key / watched) *}
<button type="button" class="mp-watch{if $x.watched} is-on{/if}" data-watch="{$x.watch_key}" title="{Lang::T('Add to watchlist')}" aria-pressed="{if $x.watched}true{else}false{/if}"><i class="fa fa-star"></i></button>
