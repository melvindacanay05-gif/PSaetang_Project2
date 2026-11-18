<?php
$answer = '';
$placeholders[] = [1,2,3,4,5];
$food_answers[] = [
                    ['pizza', 20],
                    ['jollibee', 20],
                    ['halohalo', 20],
                    ['fries', 20],
                    ['burger', 20]
                    ];
$total = 0;

$answer = $_POST['answer'];

foreach ($food_answers as $fanswer){
    foreach ($fanswer as [$val, $val2]){
        echo $val . $val2;
    }
}
// $food_answers.foreach(var );

?>

<html>
    <head>

    <body>
        <h1>Favorite food</h1>
        <h2><?=print_r($placeholders[0][0])?></h2>
        <h2><?=print_r($placeholders[0][1])?></h2>
        <h2><?=print_r($placeholders[0][2])?></h2>
        <h2><?=print_r($placeholders[0][3])?></h2>
        <h2><?=print_r($placeholders[0][4])?></h2>

        <form method="POST" action="game.php">
            <input type="text" name="answer">
            <button type="submit">submit</button>
        </form>
    </body>
</html>