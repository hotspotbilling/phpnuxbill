<div class="box box-info box-solid">
    <div class="box-header">
        <h3 class="box-title">{Lang::T('Announcement')}</h3>
    </div>
    <div class="box-body">
        {$Announcement_Customer = "{$PAGES_PATH}/Announcement_Customer.html"}
        {if file_exists($Announcement_Customer)}
            {include file=$Announcement_Customer}
        {/if}
    </div>
</div>