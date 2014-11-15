<h2>Bloom Self Assessment Scores</h2>
<div class="instructions">
    As a reminder, here's what your scores mean.<br>
    1 = Help!<br>
    2 = Not so great<br>
    3 = Okay<br>
    4 = Pretty good<br>
    5 = Wonderful<br>
    <br>
    When you set your goals, you'll probably want to focus on the areas where you scored between 1 and 3.
    Don't worry. We all have a ways to go in many areas!
</div>

<table class="assessment_results">
    <thead>
    <tr>
        <th>Categories and Questions</th>
        <?php foreach ( $assessments as $a ): ?>
            <th><?php echo date("M j",strtotime($a['assessment_date'])); ?></th>
        <?php endforeach; ?>
        <th>Average</th>
    </tr>
    </thead>
    <tbody>
    <?php $level = 0; ?>
    <?php echo $this->get_partial('assessments.summary.table', compact(['assessments','hierarchy','level'])); ?>
    <tr>
        <th>Averages</th>
        <?php $avg = []; foreach($assessments as $a): ?>
        <th><?php echo $avg[] = $a['average']; ?></th>
        <?php endforeach; ?>
        <th><?php echo round(array_sum($avg)/count($avg),1); ?></th>
    </tr>
    </tbody>
</table>

<button onclick="window.location.search=''">Back</button>