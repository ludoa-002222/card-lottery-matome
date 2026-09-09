// macOS標準のVision（被写体抽出）で商品写真の背景を透過にする。
//
// しきい値によるフラッドフィル方式では、モンスターボールの「白い下半分」と背景が
// ほぼ同色かつ隣接しているため必ず背景が被写体側へ漏れた。
// Vision の VNGenerateForegroundInstanceMaskRequest は色ではなく被写体を認識するため、
// この問題が起きない。
//
// 使い方: swift CutSubject.swift <入力> <出力.png>

import Foundation
import Vision
import CoreImage
import AppKit

let args = CommandLine.arguments
guard args.count >= 3 else {
    FileHandle.standardError.write("usage: CutSubject.swift <in> <out.png>\n".data(using: .utf8)!)
    exit(2)
}
let inURL = URL(fileURLWithPath: args[1])
let outURL = URL(fileURLWithPath: args[2])

guard let ciImage = CIImage(contentsOf: inURL) else {
    FileHandle.standardError.write("画像を読み込めません: \(args[1])\n".data(using: .utf8)!)
    exit(1)
}

let handler = VNImageRequestHandler(ciImage: ciImage, options: [:])
let request = VNGenerateForegroundInstanceMaskRequest()

do {
    try handler.perform([request])
    guard let observation = request.results?.first else {
        FileHandle.standardError.write("被写体を検出できませんでした\n".data(using: .utf8)!)
        exit(3)
    }
    // 検出された全インスタンスをまとめて切り抜く
    let pixelBuffer = try observation.generateMaskedImage(
        ofInstances: observation.allInstances,
        from: handler,
        croppedToInstancesExtent: false
    )
    let masked = CIImage(cvPixelBuffer: pixelBuffer)
    let ctx = CIContext()
    guard let colorSpace = CGColorSpace(name: CGColorSpace.sRGB) else { exit(4) }
    try ctx.writePNGRepresentation(of: masked, to: outURL, format: .RGBA8, colorSpace: colorSpace)
    print("  OK: \(outURL.lastPathComponent)  被写体数=\(observation.allInstances.count)")
} catch {
    FileHandle.standardError.write("失敗: \(error)\n".data(using: .utf8)!)
    exit(1)
}
