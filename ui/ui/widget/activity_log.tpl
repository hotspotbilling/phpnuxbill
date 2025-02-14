<div class="panel panel-info panel-hovered mb20 activities">
    <div class="panel-heading"><a href="{Text::url('logs')}">{Lang::T('Activity Log')}</a></div>
    <div class="panel-body">
        <ul class="list-unstyled">
            {foreach $dlog as $dlogs}
                <li class="primary">
                    <span class="point"></span>
                    <span class="time small text-muted">{Lang::timeElapsed($dlogs['date'],true)}</span>
                    <p>{$dlogs['description']}</p>
                </li>
            {/foreach}
        </ul>
    </div>
</div>