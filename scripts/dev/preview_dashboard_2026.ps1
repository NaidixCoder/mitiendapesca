Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing
Start-Process 'C:\Program Files\Mozilla Firefox\firefox.exe' -ArgumentList @('-new-window','http://localhost/mi-pesca/public_html/admin')
Start-Sleep -Seconds 5
$p='C:\xampp\mi-pesca-backups\dashboard_2026_render.png'
$b=[System.Windows.Forms.Screen]::PrimaryScreen.Bounds
$bmp=New-Object System.Drawing.Bitmap($b.Width,$b.Height)
$gfx=[System.Drawing.Graphics]::FromImage($bmp)
$gfx.CopyFromScreen($b.Location,[System.Drawing.Point]::Empty,$b.Size)
$bmp.Save($p,[System.Drawing.Imaging.ImageFormat]::Png)
$gfx.Dispose();$bmp.Dispose()
[System.Windows.Forms.SendKeys]::SendWait('^w')
Write-Output $p
