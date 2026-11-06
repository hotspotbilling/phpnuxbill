<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />

    <script>
        var appUrl = '{$app_url}';
    </script>

    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/styles.public.css?2026.11.06.6">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fontawesome.css">
</head>

<body>
    <div class="background"></div>
    {if isset($notify) && $notify neq ''}
        <div class="public-notice public-notice--{if $notify_t eq 's'}ok{elseif $notify_t eq 'e' or $notify_t eq 'd'}bad{else}info{/if}" role="alert">{$notify|escape|replace:'&lt;br&gt;':'<br>'}</div>
    {/if}