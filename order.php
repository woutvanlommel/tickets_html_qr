<?php
include 'includes/header.php'
?>

<div class="container">
  <div class="row">
    <div class="col">
      <h1>Order</h1>
      <?php if (isset($_SESSION['email'])) { ?>
        <p>Hello <?php echo $_SESSION['name'] ?></p>
        <h2>Please order your tickets here</h2>
        <p>Amount EUR 45 per ticket</p>
        <form action="orderprocess.php" method="post">
          <input type="number" class="form-control" name="amount">
          <p class="small">Tickets are not refundable; please use your common sense and to not sell them to the rest of the western world</p>
          <button class="btn btn-primary">Order</button>
        </form>
      <?php } else {
        echo "<h2>Acces denied</h2>";
        echo "<p>Please login</p>";
      }
      ?>

    </div>
  </div>
</div>