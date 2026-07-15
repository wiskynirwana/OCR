import sys
import cv2
import numpy as np


def threshold_profile(img):
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    _, out = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    return out


def blue_flatten(img):
    h, w = img.shape[:2]

    # AKTE punya background biru/cyan.
    # Di channel biru, background jadi terang dan teks tetap gelap.
    b, g, r = cv2.split(img)

    # Ratain background:
    # morphological close = nebak bentuk background besar
    # divide = ngurangin tekstur biru + cahaya gak rata
    k = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (51, 51))
    bg = cv2.morphologyEx(b, cv2.MORPH_CLOSE, k)
    norm = cv2.divide(b, bg, scale=255)

    # Bikin binary: teks hitam, background putih
    _, th = cv2.threshold(
        norm,
        0,
        255,
        cv2.THRESH_BINARY + cv2.THRESH_OTSU
    )

    # Crop baris nama anak di akte
    # Ini posisi yang tadi kebukti pas buat file akte lu
    line = th[
        int(h * 0.535):int(h * 0.550),
        int(w * 0.20):int(w * 0.85)
    ]

    lh, lw = line.shape

    # Buang titik-titik leader / garis putus-putus.
    # Nama punya tinta lebih padat dibanding titik-titik.
    inv = (255 - line) > 0
    colsum = inv.sum(0).astype(float)
    cols = np.where(colsum > lh * 0.30)[0]

    if len(cols):
        c0, c1 = int(cols.min()), int(cols.max())
    else:
        c0, c1 = 0, lw

    crop = line[:, max(0, c0 - 15):c1 + 15]

    # Perbesar biar Tesseract enak baca
    out = cv2.resize(crop, None, fx=3, fy=3, interpolation=cv2.INTER_CUBIC)

    # Bersihin lagi setelah resize
    _, out = cv2.threshold(out, 127, 255, cv2.THRESH_BINARY)

    # Kasih border putih biar teks gak mepet pinggir
    out = cv2.copyMakeBorder(
        out,
        50, 50, 60, 60,
        cv2.BORDER_CONSTANT,
        value=255
    )

    return out
    img = cv2.imread(in_path)              # BGR
    h, w = img.shape[:2]
    b, g, r = cv2.split(img)               # background biru → di channel BIRU jadi terang, teks gelap

    # 1) ratain background: estimasi bg via morphological close, lalu dibagi (buang tekstur biru + cahaya gak rata)
    k = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (51, 51))
    bg = cv2.morphologyEx(b, cv2.MORPH_CLOSE, k)
    norm = cv2.divide(b, bg, scale=255)
    _, th = cv2.threshold(norm, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)  # teks hitam, bg putih

    # 2) crop cuma BARIS NAMA anak (rasio tetap layout akte Dukcapil)
    line = th[int(h*0.535):int(h*0.550), int(w*0.20):int(w*0.85)]
    lh, lw = line.shape

    # 3) buang titik-titik leader: ambil kolom yang padat tinta aja
    inv = (255 - line) > 0
    colsum = inv.sum(0).astype(float)
    cols = np.where(colsum > lh*0.30)[0]
    c0, c1 = (int(cols.min()), int(cols.max())) if len(cols) else (0, lw)
    crop = line[:, max(0, c0-15):c1+15]

    # 4) perbesar 3x + bersihin + border buat tesseract
    out = cv2.resize(crop, None, fx=3, fy=3, interpolation=cv2.INTER_CUBIC)
    _, out = cv2.threshold(out, 127, 255, cv2.THRESH_BINARY)
    out = cv2.copyMakeBorder(out, 50, 50, 60, 60, cv2.BORDER_CONSTANT, value=255)
    cv2.imwrite(out_path, out)


def kk_grid_profile(img):
    # Resep KK final (tervalidasi Fase 0) + deteksi baris OTOMATIS.
    # Ngikut jumlah anggota: 1 orang atau belasan, kebaca semua.
    # Asumsi render 300 DPI (pdftoppm -r 300) -> ~2480x3509.
    h, w = img.shape[:2]

    # 1. Region kolom Nama di tabel anggota keluarga (rasio halaman)
    xa, xb = int(w * 0.100), int(w * 0.232)
    ya, yb = int(h * 0.382), int(h * 0.475)
    region = img[ya:yb, xa:xb]
    g = cv2.cvtColor(region, cv2.COLOR_BGR2GRAY)
    _, bw = cv2.threshold(g, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)
    H, Wd = bw.shape

    # 2. Deteksi garis horizontal (pemisah baris) pakai kernel horizontal panjang
    hk = cv2.getStructuringElement(cv2.MORPH_RECT, (int(Wd * 0.5), 1))
    hl = cv2.morphologyEx(bw, cv2.MORPH_OPEN, hk)
    rp = hl.sum(axis=1)
    thr = 0.5 * rp.max()
    raw = [y for y in range(H) if rp[y] > thr]
    lines = []
    if raw:
        cur = [raw[0]]
        for v in raw[1:]:
            if v - cur[-1] <= 5:
                cur.append(v)
            else:
                lines.append(int(sum(cur) / len(cur)))
                cur = [v]
        lines.append(int(sum(cur) / len(cur)))

    # 3. Ambil HANYA baris yang ada tulisannya (baris kosong di-skip)
    imgs = []
    for i in range(len(lines) - 1):
        top, bot = lines[i], lines[i + 1]
        if bot - top < 18:
            continue
        cell = g[top + 5:bot - 5, :]  # margin 5px buang garis tanpa hapus manual
        if cell.size == 0:
            continue
        if (cell < 128).mean() < 0.04:  # baris kosong -> skip
            continue
        _, t = cv2.threshold(cell, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        imgs.append(t)  # hitam-putih -> tesseract bisa baca (grayscale malah kosong)

    if not imgs:
        raise RuntimeError("Gak ada nama kedeteksi di tabel KK")

    print(f"[debug] jumlah nama kedeteksi = {len(imgs)}", file=sys.stderr)

    # 4. Tumpuk semua nama + sekat putih antar baris
    W = max(i.shape[1] for i in imgs)
    sep = np.full((45, W), 255, np.uint8)
    canvas = [sep]
    for t in imgs:
        pad = np.full((t.shape[0], W), 255, np.uint8)
        pad[:, :t.shape[1]] = t
        canvas += [pad, sep]
    stack = np.vstack(canvas)

    # 5. Perbesar 3x + hitam-putihkan + bingkai putih (bagus buat OCR)
    stack = cv2.resize(stack, None, fx=3, fy=3, interpolation=cv2.INTER_CUBIC)
    _, stack = cv2.threshold(stack, 127, 255, cv2.THRESH_BINARY)
    stack = cv2.copyMakeBorder(stack, 40, 40, 60, 60, cv2.BORDER_CONSTANT, value=255)
    return stack


def main():
    in_path = sys.argv[1]
    out_path = sys.argv[2]
    profile = sys.argv[3]

    img = cv2.imread(in_path)
    if img is None:
        print(f"ERROR: gagal baca gambar: {in_path}", file=sys.stderr)
        sys.exit(1)

    if profile == "threshold":
        out = threshold_profile(img)
    elif profile == "blue_flatten":
        out = blue_flatten(img)
    elif profile == "kk_grid":
        out = kk_grid_profile(img)
    else:
        print(f"ERROR: profil gak dikenal: {profile}", file=sys.stderr)
        sys.exit(1)

    cv2.imwrite(out_path, out)
    print(out_path)


if __name__ == "__main__":
    main()
