<div class="instructions">
    <h3>Instructions</h3>

    <p>If you need a reminder of how the whole game works,
        <a href='http://powerofmoms.com/empowering-opportunities/the-bloom-game/instructions/'
           onClick="window.open('http://powerofmoms.com/empowering-opportunities/the-bloom-game/instructions/', '_blank', 'width=800,height=600,scrollbars=yes,status=yes,resizable=yes,screenx=0,screeny=0,top=0,left=0'); return false;">click
            here to review the overall game instructions</a> .</p>

    <p>Before you begin the Bloom Game, please take this quick self-assessment. We'll total your score
        (keeping it private, of course) and invite you to do this again every three months while you're growing
        your best self. Think how great it will be to look at your past and present scores and see how you're
        progressing. THAT is power.</p>

    <p><strong>For each question, click below the number that best
            corresponds to how you feel about that aspect of your life.</strong><br/>
        1 = Help!<br/>
        2 = Not so great<br/>
        3 = Okay<br/>
        4 = Pretty good<br/>
        5 = Wonderful</p>

    <p>When you're finished, click "Submit your assessment" and you'll be directed to your personal assessment page
        where you'll see your score displayed. Each time you take this assessment, you'll be able to compare your
        current and past scores. You can look at your personal assessment page anytime. </p>

    <p>Relax, breathe, and be totally honest...this is just for you.</p>
</div>
<h2>New Assessment for <?php echo $current_user->user_nicename; ?></h2>
<form class="assessment_create" method="post">
    <?php echo $this->generate_questions(); ?>
</form>