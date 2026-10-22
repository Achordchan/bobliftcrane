# Drawing data

Figures read off the technical department's drawings, one JSON per model. The site uses the merged
`inc/drawing-data.json` (working radius, lifting height, boom angle, outrigger span, load chart), applied to the
English products by `inc/product-data-2026-10.php`.

Source drawings are the original PDFs on the operations Mac (`~/Desktop/工作/随车吊`, `~/Desktop/工作/船吊`,
`~/Desktop/工作/叉车飞臂吊`); models without an original were read from the brochure crops. Lifting heights are read
off the chart grid to 0.5 m and shown with "≈". When the technical department corrects a drawing, update the
model's file here and in `inc/drawing-data.json`, and bump the option name in `inc/product-data-2026-10.php`.
