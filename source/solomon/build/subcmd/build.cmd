@REM
@REM build
@REM


@REM
@REM 準備
@REM

@CALL VERSION.cmd


@REM
@REM version.json の作成
@REM

@ECHO "%VERSION_MAJOR%.%VERSION_MINOR%.%VERSION_BUILD%" >web\version.json


@REM
@REM 本体処理
@REM

@REM コンパイル結果のコピー先ディレクトリの作成
@SET RESULT_DIR=%SOLOMON_SNAPSHOT_DIR%\result\%BUILD_NAME%
@IF NOT EXIST "%RESULT_DIR%" CALL "%SOLOMON_LIBCMD_DIR%\mkdir.ex.cmd" "%RESULT_DIR%"

@REM コンパイル結果ディレクトリにバージョン情報ファイルが存在していたら削除
@IF EXIST "%RESULT_DIR%\VERSION.cmd" DEL "%RESULT_DIR%\VERSION.cmd"

@REM コンパイル結果をコピー
@CALL "%SOLOMON_MAKE_SNAPSHOT_CMD%" ".\web" "%RESULT_DIR%\web" >NUL
@COPY /Y ".\VERSION.cmd" "%RESULT_DIR%" >NUL

@REM abraham\twitteroauth をコピー
@CALL "%SOLOMON_MIRROR_DIR_CMD%" "..\..\..\abraham\twitteroauth\src" "%RESULT_DIR%\web\api\abraham\twitteroauth"

@REM mbostock\d3 をコピー
@CALL "%SOLOMON_LIBCMD_DIR%\mkdir.ex.cmd" "%RESULT_DIR%\web\js\mbostock\d3"
@COPY /Y "..\..\..\mbostock\d3\d3.min.js" "%RESULT_DIR%\web\js\mbostock\d3" >NUL

@CALL "%SOLOMON_COMPILE_SUCCESS_CMD%"
@CALL "%SOLOMON_TEST_SUCCESS_CMD%"