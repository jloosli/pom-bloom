<h2>Progress Overview for <?php echo $current_user->user_nicename ?></h2>
<div class="instructions">
    The progress you record on your Goal Tracking Sheet shows up here. Click on the date to update your progress.
</div>
<table class="overview">
    <thead>
    <tr>
        <th>Category</th>
        <?php foreach ( $goalsets as $gs ): ?>
            <th><?php echo $gs->name; ?></th>
        <?php endforeach; ?>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ( $categories as $cat ): ?>
        <tr>
            <th><?php echo $cat->name; ?></th>
            <?php foreach($goalsets as $gs): ?>
            <td>x</td>
            <?php endforeach; ?>
            <th>2</th>
        </tr>
    <?php endforeach; ?>
    <tr>
        <th>Serendipity</th>
        <?php foreach($goalsets as $gs): ?>
            <td>x</td>
        <?php endforeach; ?>
        <th>2</th>
    </tr>
    <tr>
        <th>Totals</th>
        <?php foreach($goalsets as $gs): ?>
            <th>x</th>
        <?php endforeach; ?>
        <th>2</th>
    </tr>
    </tbody>
</table>
<h3>Available Actions</h3>
<ul class="actions">
    <li><a href="#">Read over the instructions for Bloom</a></li>
    <li><a href="#">Print your current list of goals</a></li>
    <li><a href="#">Update your game preferences</a></li>
    <li><a href="#">Look at previously completed self assessments</a></li>
</ul>