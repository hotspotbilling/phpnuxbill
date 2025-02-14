{if $run_date}
    {assign var="current_time" value=$smarty.now}
    {assign var="run_time" value=strtotime($run_date)}
    {if $current_time - $run_time > 3600}
        <div class="panel panel-cron-warning panel-hovered mb20 activities">
            <div class="panel-heading"><i class="fa fa-clock-o"></i> &nbsp; {Lang::T('Cron has not run for over 1 hour. Please
                check your setup.')}</div>
        </div>
    {else}
        <div class="panel panel-cron-success panel-hovered mb20 activities">
            <div class="panel-heading">{Lang::T('Cron Job last ran on')}: {$run_date}</div>
        </div>
    {/if}
{else}
    <div class="panel panel-cron-danger panel-hovered mb20 activities">
        <div class="panel-heading"><i class="fa fa-warning"></i> &nbsp; {Lang::T('Cron appear not been setup, please check
                your cron setup.')}</div>
    </div>
{/if}