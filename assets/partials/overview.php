<h2>Progress Overview for <?php echo $current_user->user_nicename ?></h2>
<div class="instructions">
    The progress you record on your Goal Tracking Sheet shows up here. Click on the date to update your progress.
</div>
<table class="overview">
    <thead>
    <tr>
        <th>Category</th>

        <?php foreach ( $goalsets as $gs ): ?>
            <th>
                <?php printf("<a href='%s?page=goals.update&goalset=%s'>%s</a>",
                    str_replace("?","", $_SERVER["REQUEST_URI"]) ,
                    $gs->name,
                    $gs->name); ?>
            </th>
        <?php endforeach; ?>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    <?php $total_gs = []; foreach ( $categories as $cat ):  ?>
        <tr>
            <th><?php echo $cat->name; ?></th>
            <?php  $total = 0; foreach($goalsets as $gs): $total_gs[$gs->name] = 0;?>
            <td><?php
                    foreach($goals[$gs->name][$cat->name] as $goal) {
                        printf("<img title = '%s' src='%s' /> ",
                            $goal->post_title,
                            $this->parent->assets_url.'/images/' .
                            ($goal->is_completed ? 'accept' : 'cross') .
                                                             '.png'
                        );

                        $total += $goal->is_completed;
                        $total_gs[$gs->name] += $goal->is_completed;

                        } ?>
                </td>
            <?php endforeach; ?>
            <th><?php echo $total; ?></th>
        </tr>
    <?php endforeach; ?>
    <tr>
        <th>Serendipity</th>
        <?php $total=0; foreach($goalsets as $gs): ?>
            <td><?php
                foreach($goals[$gs->name]['serendipity'] as $goal) {
                    printf( "<img src='%s' /> ",
                        $this->parent->assets_url . '/images/' .
                        ( $goal->is_completed ? 'accept' : 'cross' ) .
                        '.png'
                    );
                    $total += $goal->is_completed;
                    $total_gs[$gs->name] += $goal->is_completed;
                } ?>
            </td>
        <?php endforeach; ?>
        <th><?php echo $total; ?></th>
    </tr>
    <tr>
        <th>Totals</th>
        <?php foreach($goalsets as $gs): ?>
            <th><?php echo $total_gs[$gs->name]; ?></th>
        <?php endforeach; ?>
        <th><?php echo array_sum($total_gs); ?></th>
    </tr>
    </tbody>
</table>
<h3>Available Actions</h3>
<ul class="actions">
    <li><a href="javascript:window.location.search='page=instructions'">Read over the instructions for Bloom</a></li>
    <li><a href="javascript:window.location.search='page=goals.print'">Print your current list of goals</a></li>
    <li><a href="javascript:window.location.search='page=preferences'">Update your game preferences</a></li>
    <li><a href="javascript:window.location.search='page=assessments'">Look at previously completed self assessments</a></li>
    <li><a href="javascript:window.location.search='page=goals.set'">Set some new goals</a></li>
</ul>