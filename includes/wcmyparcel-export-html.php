<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		require_once(ABSPATH . 'wp-admin/admin.php');
		wp_register_style( 'wcmyparcel-admin-styles', dirname(plugin_dir_url(__FILE__)) .  '/css/wcmyparcel-admin-styles.css', array(), '', 'all' );
		wp_enqueue_style( 'wcmyparcel-admin-styles' );		
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
		wp_enqueue_script( 'jquery' );
		do_action('admin_print_styles');
		do_action('admin_print_scripts');
	?>
</head>
<body>
<form  method="post" class="page-form">						
	<table class="widefat">
	<thead>
		<tr>
			<th>Export opties</td>
		</tr>
	</thead>
	<tbody>
		<?php $c = true; foreach ($data as $row) : ?>
		<tr <?php echo (($c = !$c)?' class="alternate"':'');?>>
			<td>
				<table>
					<tr>
						<td colspan="2"><strong>Bestelling <?php echo $row['ordernr']; ?></strong></td>
					</tr>
					<tr>
						<td class="ordercell">
							<table class="widefat">
								<thead>
									<tr>
										<th>#</th>
										<th>Productnaam</th>
										<th align="right">Gewicht (kg)</th>
									</tr>
								</thead>
								<tbody>
								<?php
								$verpakkingsgewicht = (isset($this->settings['verpakkingsgewicht'])) ? preg_replace("/\D/","",$this->settings['verpakkingsgewicht'])/1000 : 0;
								$total_weight = $verpakkingsgewicht;
								foreach ($row['bestelling'] as $product) { 
									$total_weight += $product['total_weight'];?>
									<tr>
										<td><?php echo $product['quantity'].'x'; ?></td>
										<td><?php echo $product['name'].$product['variation']; ?></td>
										<td align="right"><?php echo number_format($product['total_weight'], 3, ',', ' '); ?></td>
									</tr>
								<?php } ?>
									<tr>
										<td>&nbsp;</td>
										<td>Standaard verpakking</td>
										<td align="right"><?php echo number_format($verpakkingsgewicht, 3, ',', ' '); ?></td>
									</tr>
								</tbody>
								<tfoot>
									<tr>
										<td></td>
										<td>Totaal:</td>
										<td align="right"><?php echo number_format($total_weight, 3, ',', ' ');?></td>
									</tr>
								</tfoot>
							</table>
						</td>
						<td><p><?php
							if ( $row['landcode'] == 'NL' && ( empty($row['straat']) || empty($row['huisnummer']) ) ) { ?>
							<span style="color:red">Deze order bevat geen geldige straatnaam- en huisnummergegevens, en kan daarom niet worden ge-exporteerd! Waarschijnlijk is deze order geplaatst voordat de MyParcel plugin werd geactiveerd. De gegevens kunnen wel handmatig worden ingevoerd in het order scherm.</span>
							</p>
						</td>
					</tr>
							<?php } else {
							echo $row['formatted_address'].'<br/>'
							.$row['telefoon'].'<br/>'
							.$row['email']; ?></p>
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][naam]" value="<?php echo $row['naam'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][bedrijfsnaam]" value="<?php echo $row['bedrijfsnaam'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][straat]" value="<?php echo $row['straat'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][huisnummer]" value="<?php echo $row['huisnummer'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][huisnummertoevoeging]" value="<?php echo $row['huisnummertoevoeging'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][adres1]" value="<?php echo $row['adres1'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][adres2]" value="<?php echo $row['adres2'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][postcode]" value="<?php echo $row['postcode'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][woonplaats]" value="<?php echo $row['woonplaats'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][landcode]" value="<?php echo $row['landcode'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][gewicht]" value="<?php echo number_format($total_weight, 2, '.', ''); ?>">
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<table class="wcmyparcel_settings_table">
								<tr>
									<?php if (!isset($this->settings['email'])) $this->settings['email'] = ''; ?>
									<td>Email adres koppelen</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][email]" value="<?php echo $row['email']; ?>" <?php checked("1", $this->settings['email'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['telefoon'])) $this->settings['telefoon'] = ''; ?>
									<td>Telefoonnummer koppelen</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][telefoon]" value="<?php echo $row['telefoon']; ?>" <?php checked("1", $this->settings['telefoon'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['extragroot'])) $this->settings['extragroot'] = ''; ?>
									<td>Extra groot formaat (+ &euro; 2.19)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][extragroot]" value="x" <?php checked("1", $this->settings['extragroot'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['huisadres'])) $this->settings['huisadres'] = ''; ?>
									<td>Niet bij buren bezorgen (+ &euro; 0.26)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][huisadres]" value="x" <?php checked("1", $this->settings['huisadres'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['handtekening'])) $this->settings['handtekening'] = ''; ?>
									<td>Handtekening voor ontvangst (+ &euro; 0.33)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][handtekening]" value="x" <?php checked("1", $this->settings['handtekening'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['huishand'])) $this->settings['huishand'] = ''; ?>
									<td>Niet bij buren bezorgen + Handtekening voor ontvangst (+ &euro; 0.40)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][huishand]" value="x" <?php checked("1", $this->settings['huishand'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['huishandverzekerd'])) $this->settings['huishandverzekerd'] = ''; ?>
									<td>Niet bij buren bezorgen + Handtekening voor ontvangst + verzekerd tot &euro; 50 (+ &euro; 0.50)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][huishandverzekerd]" value="x" <?php checked("1", $this->settings['huishandverzekerd'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['retourbgg'])) $this->settings['retourbgg'] = ''; ?>
									<td>Retour bij geen gehoor</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][retourbgg]" value="x" <?php checked("1", $this->settings['retourbgg'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['verzekerd'])) $this->settings['verzekerd'] = ''; ?>
									<td>Verhoogd aansprakelijk (+ &euro; 1.58 per &euro; 500 verzekerd)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][verzekerd]" value="x" <?php checked("1", $this->settings['verzekerd'])?>></td>
								<tr>
									<td>Verzekerd bedrag (afgerond in hele in &euro;)</td>
									<td><input type="text" name="data[<?php echo $row['orderid']; ?>][verzekerdbedrag]" value="" size="5"></td>						
								</tr>
								<tr>
									<?php if (!isset($this->settings['bericht'])) $this->settings['bericht'] = '';
									$this->settings['bericht'] = str_replace('[ORDER_NR]', $row['ordernr'], $this->settings['bericht']);
									?>
									<td>Optioneel bericht (niet op label, wel in track&trace)</td>
									<td><input type="text" name="data[<?php echo $row['orderid']; ?>][bericht]" value="<?php echo $this->settings['bericht']; ?>"></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['kenmerk'])) $this->settings['kenmerk'] = '';
									$this->settings['kenmerk'] = str_replace('[ORDER_NR]', $row['ordernr'], $this->settings['kenmerk']);
									?>
									<td>Eigen kenmerk (linksboven op label)</td>
									<td><input type="text" name="data[<?php echo $row['orderid']; ?>][kenmerk]" value="<?php echo $this->settings['kenmerk']; ?>"></td>
								</tr>
							</table>
						</td>
					</tr>
					<?php } // end else ?>
				</table>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
	</table>
<input type="hidden" name="action" value="wcmyparcel-export">
<div class="submit-wcmyparcel">
	<input type="submit" value="Exporteer naar MyParcel" class="button-wcmyparcel">
	<img src="<?php echo dirname(plugin_dir_url(__FILE__)).'/img/wpspin_light.gif';?>" class="waiting"/>
</div>
</form>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$('.button-wcmyparcel').click(function(){
			$('.waiting').show();
		});
	});
</script>

</body>
</html>
