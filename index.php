<?php
// cgpa_form.php — Single-page "Google Form"-style CGPA guesser with funny suggestions
// No database, no frameworks. Pure PHP + HTML + CSS. © You :)

// ---------- Helpers ----------
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Questions + options (weights: 0=worst ... 3=best)
$questions = [
  'study_hours' => [
    'label' => 'On a typical day, how long do you study (outside class)?',
    'options' => [
      'a' => ['label'=>'0–1 hour (blink-and-done)',          'w'=>0],
      'b' => ['label'=>'1–2 hours (warm-up only)',            'w'=>1],
      'c' => ['label'=>'2–4 hours (respectable grind)',       'w'=>2],
      'd' => ['label'=>'4+ hours (academic athlete)',         'w'=>3],
    ]
  ],
  'attendance' => [
    'label' => 'Class attendance consistency?',
    'options' => [
      'a' => ['label'=>'< 60% ("See you next midterm!")',     'w'=>0],
      'b' => ['label'=>'60–75% (cameo appearances)',          'w'=>1],
      'c' => ['label'=>'75–90% (regular human)',              'w'=>2],
      'd' => ['label'=>'90–100% (front-bench aura)',          'w'=>3],
    ]
  ],
  'assignment_timely' => [
    'label' => 'Assignments are usually…',
    'options' => [
      'a' => ['label'=>'Late & legendary excuses',            'w'=>0],
      'b' => ['label'=>'Sometimes late (we try!)',            'w'=>1],
      'c' => ['label'=>'Mostly on time',                      'w'=>2],
      'd' => ['label'=>'Early (who IS this person?)',         'w'=>3],
    ]
  ],
  'procrastination' => [
    'label' => 'Procrastination level?',
    'options' => [
      'a' => ['label'=>'Master Procrastinator (tomorrow-land)', 'w'=>0],
      'b' => ['label'=>'Often… after one more video',            'w'=>1],
      'c' => ['label'=>'Sometimes, but I recover',               'w'=>2],
      'd' => ['label'=>'Rarely—Do it now mentality',             'w'=>3],
    ]
  ],
  'sleep_hours' => [
    'label' => 'Average sleep on weekdays?',
    'options' => [
      'a' => ['label'=>'< 5 hours (goblin mode)',             'w'=>0],
      'b' => ['label'=>'5–6 hours (yawn-powered)',            'w'=>1],
      'c' => ['label'=>'6–7 hours (functional)',              'w'=>2],
      'd' => ['label'=>'7–8 hours (peak human)',              'w'=>3],
    ]
  ],
  'exam_strategy' => [
    'label' => 'Exam prep strategy?',
    'options' => [
      'a' => ['label'=>'Wing it + vibes',                     'w'=>0],
      'b' => ['label'=>'Skim the night before',               'w'=>1],
      'c' => ['label'=>'Make summaries & practice',           'w'=>2],
      'd' => ['label'=>'Spaced practice + past papers',       'w'=>3],
    ]
  ],
  'study_mode' => [
    'label' => 'Study environment you actually use most:',
    'options' => [
      'a' => ['label'=>'Chaotic group (more tea than notes)', 'w'=>0],
      'b' => ['label'=>'Chatty friends (50/50 focus)',        'w'=>1],
      'c' => ['label'=>'Focused duo or quiet corner',         'w'=>2],
      'd' => ['label'=>'Solo deep work + occasional group',   'w'=>3],
    ]
  ],
  'device_distraction' => [
    'label' => 'Phone/social distractions while studying:',
    'options' => [
      'a' => ['label'=>'Non-stop scrolling (thumb gets cardio)', 'w'=>0],
      'b' => ['label'=>'Frequent breaks (oops again)',            'w'=>1],
      'c' => ['label'=>'Scheduled breaks (timer saves me)',       'w'=>2],
      'd' => ['label'=>'Phone in another room (ninja focus)',     'w'=>3],
    ]
  ],
  'help_seeking' => [
    'label' => 'When you get stuck, you…',
    'options' => [
      'a' => ['label'=>'Wait for a miracle / meltdown',        'w'=>0],
      'b' => ['label'=>'Random blogs + guesswork',             'w'=>1],
      'c' => ['label'=>'Ask peers & check official docs',      'w'=>2],
      'd' => ['label'=>'Plan with TA/teacher early',           'w'=>3],
    ]
  ],
  'health_habits' => [
    'label' => 'Energy & health habits?',
    'options' => [
      'a' => ['label'=>'Energy drinks + chips for dinner',     'w'=>0],
      'b' => ['label'=>'Some water, random meals',             'w'=>1],
      'c' => ['label'=>'Balanced-ish, short walks',            'w'=>2],
      'd' => ['label'=>'Hydration + regular exercise',         'w'=>3],
    ]
  ],
];

// Suggestion triggers for specific weaker choices
$sillyTips = [
  'study_hours' => [
    'a'=>"Your books miss you. Schedule 'study dates'—bring snacks, make it official.",
    'b'=>"Upgrade to a focused 90-minute block. Your GPA wants more screen time than memes."
  ],
  'attendance' => [
    'a'=>"Attend like your crush is taking attendance.",
    'b'=>"Two extra classes a week = surprise brain gains."
  ],
  'assignment_timely' => [
    'a'=>"Submit on time. Even your Wi-Fi will be proud.",
    'b'=>"Set a fake deadline 24h earlier. Future-you says thanks."
  ],
  'procrastination' => [
    'a'=>"If it takes less than 2 minutes, do it now. Not 'tomorrow-ish'.",
    'b'=>"Try Pomodoro: 25 on, 5 off. Bribe yourself with chai."
  ],
  'sleep_hours' => [
    'a'=>"Blue light is not your soulmate. Pillow before midnight.",
    'b'=>"One extra hour of sleep = free brain upgrade."
  ],
  'exam_strategy' => [
    'a'=>"‘Wing it’ is a bird strategy, not an exam plan.",
    'b'=>"Replace cramming with spaced reps. Memory isn’t a USB stick."
  ],
  'study_mode' => [
    'a'=>"Group study ≠ group gossip. Try a 'silent 25' rule.",
    'b'=>"Seat the chatty friend far, far away. Like Pluto."
  ],
  'device_distraction' => [
    'a'=>"DND on. If it’s urgent, they’ll send a pigeon.",
    'b'=>"Stack notifications till break time. You’re not a 24/7 helpdesk."
  ],
  'help_seeking' => [
    'a'=>"Ask early. Confusion is cheaper on Monday than on deadline day.",
    'b'=>"Official docs first, then friends, then memes."
  ],
  'health_habits' => [
    'a'=>"Replace one energy drink with water. Your heart says ‘lovely idea’.",
    'b'=>"Carry a bottle. Water is the original performance drink."
  ]
];

// Process submission
$isSubmitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$errors = [];
$cgpa = null;
$tierMsg = '';
$funny = [];

if ($isSubmitted) {
  // Basic identity fields (optional)
  $name = trim($_POST['name'] ?? '');
  $dept = trim($_POST['dept'] ?? '');

  $sum = 0; $answered = 0; $qCount = count($questions);

  foreach ($questions as $key => $q) {
    if (!isset($_POST[$key])) {
      $errors[] = "Please answer: ".$q['label'];
      continue;
    }
    $choice = $_POST[$key];
    if (!isset($q['options'][$choice])) {
      $errors[] = "Invalid choice for: ".$q['label'];
      continue;
    }
    $w = $q['options'][$choice]['w'];
    $sum += $w;
    $answered++;

    // Add targeted suggestion for weaker selections
    if (isset($sillyTips[$key][$choice])) {
      $funny[] = $sillyTips[$key][$choice];
    }
  }

  if ($answered === $qCount) {
    // Average on 0..3 scale, map to 2.00..4.00
    $avg = $sum / ($qCount * 3);             // 0..1
    $cgpa = round(2 + ($avg * 2), 2);        // 2..4

    // Tier-based headline
    if      ($cgpa >= 3.6) { $tierMsg = "Dean’s-list vibes! Humble flex permitted."; }
    elseif  ($cgpa >= 3.2) { $tierMsg = "Strong form—keep the momentum rolling."; }
    elseif  ($cgpa >= 2.8) { $tierMsg = "Solid, with room for spicy upgrades."; }
    elseif  ($cgpa >= 2.4) { $tierMsg = "Passing orbit. Adjust thrusters for ascent."; }
    else                    { $tierMsg = "Plot twist time! Small habits, big glow-up."; }

    // Add a few general tips depending on CGPA band
    $bandTips = [];
    if ($cgpa < 2.6) {
      $bandTips = [
        "Front-row experiment: attention ↑, GPA ↑ (science!).",
        "Daily 45-minute focus block. Non-negotiable like breakfast.",
        "Make a tiny win before noon—confidence snowballs."
      ];
    } elseif ($cgpa < 3.2) {
      $bandTips = [
        "Upgrade notes to active recall: quiz yourself, don’t re-read.",
        "Weekly review day: clean backlog, plan next strikes.",
      ];
    } elseif ($cgpa < 3.6) {
      $bandTips = [
        "Teach a friend one topic weekly—instant mastery hack.",
        "Swap 1 scroll break with a brisk 5-minute walk."
      ];
    } else {
      $bandTips = [
        "Protect sleep like it’s exam marks in disguise.",
        "Document your system—future semesters will thank you."
      ];
    }
    $funny = array_values(array_unique(array_merge($funny, $bandTips)));
    // Limit to max 7 suggestions for brevity
    $funny = array_slice($funny, 0, 7);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CGPA Guesser — One-Page Form</title>
<style>
  :root{
    --bg:#f7f7fb;
    --card:#ffffff;
    --text:#222;
    --muted:#5f6368;
    --primary:#673ab7; /* Google Forms-ish purple */
    --accent:#8854f3;
    --ok:#0f9d58;
    --warn:#f4b400;
    --bad:#db4437;
    --shadow:0 10px 25px rgba(0,0,0,.08);
    --radius:16px;
  }
  *{box-sizing:border-box}
  body{
    margin:0; background:var(--bg); color:var(--text);
    font:16px/1.5 system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji","Segoe UI Emoji";
  }
  header{
    background:linear-gradient(135deg, var(--primary), var(--accent));
    padding:28px 16px; color:#fff; box-shadow:var(--shadow);
  }
  .wrap{ max-width:900px; margin:0 auto; padding:0 16px;}
  .brand{
    display:flex; align-items:center; gap:12px;
  }
  .logo{
    width:42px;height:42px;border-radius:12px;background:#fff1;
    display:grid;place-items:center;border:1px solid #ffffff33;
  }
  .logo span{font-weight:700;color:#fff}
  h1{margin:6px 0 2px; font-size:28px}
  .subtitle{opacity:.95}
  .card{
    background:var(--card); margin:18px 0; padding:22px; border-radius:var(--radius);
    box-shadow:var(--shadow);
  }
  .id-row{display:grid; grid-template-columns:1fr 1fr; gap:12px}
  label.title{display:block; font-weight:600; margin:6px 0 8px}
  .q{padding:16px 16px; border:1px solid #ececf3; border-radius:12px; margin:14px 0}
  .q h3{margin:0 0 8px; font-size:17px}
  .opt{display:flex; gap:10px; align-items:flex-start; padding:8px 6px}
  .opt input{transform:translateY(2px)}
  input[type="text"]{
    width:100%; padding:12px 14px; border-radius:10px; border:1px solid #dcdce6;
    outline:none; background:#fff; transition:.2s; font-size:15px;
  }
  input[type="text"]:focus{ border-color: var(--primary); box-shadow:0 0 0 4px #673ab722}
  .required{color:var(--bad); font-weight:600}
  .actions{display:flex; gap:12px; align-items:center; margin-top:14px}
  button{
    background:var(--primary); color:#fff; border:none; padding:12px 18px;
    border-radius:12px; cursor:pointer; font-weight:600; box-shadow:var(--shadow);
  }
  button.secondary{ background:#fff; color:var(--primary); border:1px solid #e5d8ff }
  .note{color:var(--muted); font-size:13px}
  .errors{border-left:4px solid var(--bad); background:#fff; padding:10px 12px; border-radius:10px; color:#a02622}
  /* Result styles */
  .result-head{display:flex; align-items:center; justify-content:space-between; gap:12px}
  .badge{
    display:inline-block; padding:6px 10px; border-radius:999px; font-weight:700; font-size:13px; color:#fff;
  }
  .b-ok{background:var(--ok)} .b-warn{background:var(--warn)} .b-bad{background:var(--bad)}
  .meter-wrap{height:12px; background:#f0ecff; border-radius:999px; overflow:hidden}
  .meter{height:100%; width:0; background:linear-gradient(90deg,#ff7675,#f4b400,#a0d911,#00c853,#00bfa5); transition:width .6s ease}
  .tips li{margin:6px 0}
  footer{color:#888; font-size:12px; padding:28px 16px; text-align:center}
  @media (max-width:640px){ .id-row{grid-template-columns:1fr} }
</style>
</head>
<body>
  <header>
    <div class="wrap">
      <div class="brand">
        <div class="logo"><span>F</span></div>
        <div>
          <h1>CGPA Guesser (Just for Fun)</h1>
          <div class="subtitle">Answer a few quick questions. Get an estimated CGPA and some cheeky life suggestions.</div>
        </div>
      </div>
    </div>
  </header>

  <main class="wrap">
    <?php if ($isSubmitted && empty($errors) && $cgpa !== null): ?>
      <section class="card" id="result">
        <div class="result-head">
          <h2 style="margin:0">Estimated CGPA: <span style="font-size:32px; font-weight:800; color:var(--primary)"><?php echo h(number_format($cgpa,2)); ?></span></h2>
          <?php
            $badgeClass = $cgpa>=3.2? 'b-ok' : ($cgpa>=2.6 ? 'b-warn' : 'b-bad');
            $badgeText  = $cgpa>=3.2? 'Great trajectory' : ($cgpa>=2.6 ? 'Keep climbing' : 'Comeback season');
          ?>
          <span class="badge <?php echo $badgeClass ?>"><?php echo $badgeText ?></span>
        </div>
        <p style="margin:8px 0 14px; color:var(--muted)"><?php echo h($tierMsg) ?></p>

        <!-- Visual meter from 2.0 to 4.0 -->
        <div class="meter-wrap" title="2.00 → 4.00 scale">
          <?php $pct = (($cgpa - 2.0) / 2.0) * 100; if($pct<0) $pct=0; if($pct>100) $pct=100; ?>
          <div class="meter" style="width: <?php echo $pct ?>%"></div>
        </div>

        <?php if (!empty($name) || !empty($dept)): ?>
          <p class="note" style="margin-top:8px">
            For: <strong><?php echo h($name ?: 'Anonymous Student'); ?></strong>
            <?php if(!empty($dept)): ?> • Department: <strong><?php echo h($dept); ?></strong><?php endif; ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($funny)): ?>
          <div class="card" style="margin-top:16px; background:#fbf9ff">
            <h3 style="margin-top:0">Friendly (and funny) suggestions</h3>
            <ul class="tips">
              <?php foreach ($funny as $tip): ?>
                <li><?php echo h($tip) ?></li>
              <?php endforeach; ?>
            </ul>
            <div class="note">These are playful nudges, not medical or academic advice. You’ve got this.</div>
          </div>
        <?php endif; ?>

        <div class="actions">
          <a href="<?php echo h($_SERVER['PHP_SELF']) ?>"><button type="button">Take Again</button></a>
          <a href="#form"><button type="button" class="secondary">Edit Answers</button></a>
        </div>
      </section>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <section class="card">
        <div class="errors">
          <strong>Oops!</strong>
          <ul>
            <?php foreach ($errors as $e): ?><li><?php echo h($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      </section>
    <?php endif; ?>

    <section class="card" id="form">
      <h2 style="margin-top:0">Student Details</h2>
      <form method="post" action="#result" novalidate>
        <div class="id-row">
          <div>
            <label class="title">Name (optional)</label>
            <input type="text" name="name" placeholder="e.g., Jony Paul" value="<?php echo h($_POST['name'] ?? '') ?>">
          </div>
          <div>
            <label class="title">Department (optional)</label>
            <input type="text" name="dept" placeholder="e.g., CSE / BBA" value="<?php echo h($_POST['dept'] ?? '') ?>">
          </div>
        </div>

        <hr style="border:none; border-top:1px solid #eee; margin:18px 0">

        <h2 style="margin:0 0 6px">Quick Questions</h2>
        <div class="note">All questions below are required <span class="required">*</span></div>

        <?php foreach ($questions as $key=>$q): ?>
          <div class="q">
            <h3><?php echo h($q['label']) ?> <span class="required">*</span></h3>
            <?php foreach ($q['options'] as $val=>$opt): 
              $checked = (($_POST[$key] ?? '') === $val) ? 'checked' : '';
            ?>
              <label class="opt">
                <input type="radio" name="<?php echo h($key) ?>" value="<?php echo h($val) ?>" required <?php echo $checked ?>>
                <span><?php echo h($opt['label']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <div class="actions">
          <button type="submit">Submit</button>
          <button type="reset" class="secondary">Reset</button>
        </div>
        <p class="note" style="margin-top:8px">CGPA is a playful estimate (range: 2.00–4.00) based on your habits.</p>
      </form>
    </section>
  </main>

  <footer>
    Built with ❤️ in one PHP file. Inspired by Google Forms styling. No data stored server-side.
  </footer>
</body>
</html>
