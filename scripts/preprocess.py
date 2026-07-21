import sys
import cv2


def threshold_profile(img):
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    _, out = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    return out


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
    else:
        print(f"ERROR: profil gak dikenal: {profile}", file=sys.stderr)
        sys.exit(1)

    cv2.imwrite(out_path, out)
    print(out_path)


if __name__ == "__main__":
    main()
