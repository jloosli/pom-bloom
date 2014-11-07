<h2>Progress Overview for <?php echo $vars['current_user']->user_nicename ?></h2>
<?php var_dump( $vars['categories'] ); ?>
<div class="instructions">
    The progress you record on your Goal Tracking Sheet shows up here. Click on the date to update your progress.
</div>
<table class="overview">
    <thead>
    <tr>
        <th>Category</th>
        <?php foreach ( $vars['goalsets'] as $gs ): ?>
            <th><?php echo $gs->name; ?></th>
        <?php endforeach; ?>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ( $vars['categories'] as $cat ): ?>
        <tr>
            <th><?php echo $cat->name; ?></th>
        </tr>
    <?php endforeach; ?>
    <tr>
        <th>Serendipity</th>
    </tr>
    <tr>
        <th>Totals</th>
    </tr>
    </tbody>
</table>