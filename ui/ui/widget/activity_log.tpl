<div class="panel panel-primary panel-hovered mb20 activities" style="overflow: hidden;">
    <div class="panel-heading"><a href="{Text::url('logs')}">{Lang::T('Activity Log')}</a></div>
    <div class="panel-body" style="overflow: auto;">
        <ul class="list-unstyled">
            {foreach $dlog as $dl}
                <li class="primary">
                    <span class="point"></span>
                    <span class="time small text-muted">{Lang::timeElapsed($dl['date'],true)}</span>
                    <p>{$dl['description']}</p>
                </li>
            {/foreach}
        </ul>
    </div>
</div>